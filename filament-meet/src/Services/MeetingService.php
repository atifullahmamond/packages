<?php

namespace Atifullahmamond\FilamentMeet\Services;

use Carbon\Carbon;
use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Str;
use Atifullahmamond\FilamentMeet\Enums\MeetingStatus;
use Atifullahmamond\FilamentMeet\Events\MeetingEnded;
use Atifullahmamond\FilamentMeet\Events\MeetingStarted;
use Atifullahmamond\FilamentMeet\Events\ParticipantJoined;
use Atifullahmamond\FilamentMeet\Events\ParticipantLeft;
use Atifullahmamond\FilamentMeet\Exceptions\MeetingException;
use Atifullahmamond\FilamentMeet\Models\Meeting;
use Atifullahmamond\FilamentMeet\Notifications\MeetingInvited;

class MeetingService
{
    /**
     * Create a new meeting, attach host as participant, send invitations.
     */
    public function createMeeting(array $data, User $host): Meeting
    {
        $uuid   = (string) Str::uuid();
        $roomId = Meeting::generateRoomId($uuid);

        $meeting = Meeting::create([
            'uuid'         => $uuid,
            'title'        => $data['title'],
            'description'  => $data['description'] ?? null,
            'room_id'      => $roomId,
            'host_id'      => $host->getKey(),
            'status'       => MeetingStatus::Scheduled,
            'scheduled_at' => $data['scheduled_at'] ?? null,
        ]);

        // Attach invited participants (not including host — host joins separately)
        if (! empty($data['participant_ids'])) {
            $meeting->participants()->syncWithoutDetaching($data['participant_ids']);

            // Send invitations
            $userModel = config('auth.providers.users.model', \App\Models\User::class);
            $users     = $userModel::whereIn('id', $data['participant_ids'])->get();
            foreach ($users as $user) {
                $user->notify(new MeetingInvited($meeting));
            }
        }

        $this->log($meeting, null, 'created', ['host_id' => $host->getKey()]);

        return $meeting;
    }

    /**
     * Transition a scheduled meeting to active. Only host can do this.
     */
    public function startMeeting(Meeting $meeting, User $user): void
    {
        if (! $meeting->isScheduled()) {
            throw new MeetingException(
                "Cannot start meeting [{$meeting->uuid}]: current status is [{$meeting->status->value}]."
            );
        }

        $meeting->update([
            'status'     => MeetingStatus::Active,
            'started_at' => Carbon::now(),
        ]);

        $this->log($meeting, $user, 'started');

        if (config('filament-meet.broadcasting_enabled', true)) {
            broadcast(new MeetingStarted($meeting))->toOthers();
        }
    }

    /**
     * Transition an active meeting to ended. Only host can do this.
     * Also marks all currently-active participants as left.
     */
    public function endMeeting(Meeting $meeting, User $user): void
    {
        if (! $meeting->isActive()) {
            throw new MeetingException(
                "Cannot end meeting [{$meeting->uuid}]: current status is [{$meeting->status->value}]."
            );
        }

        // Mark all active participants as left
        $meeting->participants()
            ->wherePivotNotNull('joined_at')
            ->wherePivotNull('left_at')
            ->each(function ($participant) use ($meeting) {
                $meeting->participants()->updateExistingPivot($participant->getKey(), [
                    'left_at' => Carbon::now(),
                ]);
            });

        $meeting->update([
            'status'   => MeetingStatus::Ended,
            'ended_at' => Carbon::now(),
        ]);

        $this->log($meeting, $user, 'ended');

        if (config('filament-meet.broadcasting_enabled', true)) {
            broadcast(new MeetingEnded($meeting))->toOthers();
        }

        // Dispatch AI summary job if enabled
        if (config('filament-meet.ai_summary_enabled', false)) {
            \Atifullahmamond\FilamentMeet\Jobs\MeetingAISummary::dispatch($meeting);
        }
    }

    /**
     * Record a user joining the meeting room.
     */
    public function joinMeeting(Meeting $meeting, User $user): void
    {
        if (! $this->canJoin($meeting, $user)) {
            throw new MeetingException(
                "User [{$user->getKey()}] is not authorized to join meeting [{$meeting->uuid}]."
            );
        }

        $isHost = (int) $meeting->host_id === (int) $user->getKey();

        // If the host starts the meeting by joining, auto-start it
        if ($isHost && $meeting->isScheduled()) {
            $this->startMeeting($meeting, $user);
            $meeting->refresh();
        }

        // Ensure pivot exists, then stamp join (handles host not synced at creation)
        $meeting->participants()->syncWithoutDetaching([$user->getKey()]);

        $meeting->participants()->updateExistingPivot($user->getKey(), [
            'joined_at' => Carbon::now(),
            'left_at'   => null,
        ]);

        $this->log($meeting, $user, 'joined');

        if (config('filament-meet.broadcasting_enabled', true)) {
            broadcast(new ParticipantJoined($meeting, $user))->toOthers();
        }
    }

    /**
     * Record a user leaving the meeting room.
     */
    public function leaveMeeting(Meeting $meeting, User $user): void
    {
        $meeting->participants()->updateExistingPivot($user->getKey(), [
            'left_at' => Carbon::now(),
        ]);

        $this->log($meeting, $user, 'left');

        if (config('filament-meet.broadcasting_enabled', true)) {
            broadcast(new ParticipantLeft($meeting, $user))->toOthers();
        }
    }

    /**
     * Determine if a user is allowed to join the meeting.
     */
    public function canJoin(Meeting $meeting, User $user): bool
    {
        if ($meeting->isEnded() || $meeting->isCancelled()) {
            return false;
        }

        // Host can always join
        if ((int) $meeting->host_id === (int) $user->getKey()) {
            return true;
        }

        if ($meeting->participants()
            ->where('user_id', $user->getKey())
            ->exists()) {
            return true;
        }

        return (bool) config('filament-meet.open_join_for_authenticated_users', true)
            && ($meeting->isActive() || $meeting->isScheduled());
    }

    /**
     * Generate a secure Jitsi JWT for a user to join this meeting.
     * Returns null if JWT is not configured.
     */
    public function generateJitsiJwt(Meeting $meeting, User $user): ?string
    {
        $appId     = config('filament-meet.jitsi_jwt_app_id');
        $appSecret = config('filament-meet.jitsi_jwt_app_secret');
        $domain    = config('filament-meet.jitsi_domain', 'meet.jit.si');

        if (! $appId || ! $appSecret) {
            return null;
        }

        // Public meet.jit.si does not validate your JWT secret; attaching one
        // commonly breaks conferencing. Omit unless explicitly enabled.
        if (strcasecmp($domain, 'meet.jit.si') === 0 && ! config('filament-meet.jwt_on_public_meet_jit_si', false)) {
            return null;
        }

        $now    = Carbon::now();
        $ttl    = (int) config('filament-meet.jwt_ttl_minutes', 120);
        $isHost = (int) $meeting->host_id === (int) $user->getKey();

        $b64Url = static fn (string $data): string => rtrim(strtr(base64_encode($data), '+/', '-_'), '=');

        $payload = [
            'context' => [
                'user' => [
                    'id'        => (string) $user->getKey(),
                    'name'      => $user->name ?? $user->email,
                    'email'     => $user->email ?? '',
                    'moderator' => $isHost,
                ],
            ],
            'aud'  => 'jitsi',
            'iss'  => $appId,
            'sub'  => $domain,
            'room' => $meeting->room_id,
            'iat'  => $now->timestamp,
            'nbf'  => $now->timestamp,
            'exp'  => $now->copy()->addMinutes($ttl)->timestamp,
        ];

        $headerSegment  = $b64Url(json_encode(['alg' => 'HS256', 'typ' => 'JWT'], JSON_UNESCAPED_SLASHES));
        $payloadSegment = $b64Url(json_encode($payload, JSON_UNESCAPED_SLASHES));
        $signingInput   = "{$headerSegment}.{$payloadSegment}";
        $signatureSeg   = $b64Url(hash_hmac('sha256', $signingInput, $appSecret, true));

        return "{$signingInput}.{$signatureSeg}";
    }

    /**
     * Generate a secure room ID from a meeting UUID using HMAC-SHA256.
     */
    public function generateSecureRoomId(Meeting $meeting): string
    {
        return Meeting::generateRoomId($meeting->uuid);
    }

    /**
     * Internal helper to write a meeting log entry.
     */
    protected function log(Meeting $meeting, ?User $user, string $event, array $payload = []): void
    {
        $meeting->logs()->create([
            'user_id' => $user?->getKey(),
            'event'   => $event,
            'payload' => empty($payload) ? null : $payload,
        ]);
    }
}
