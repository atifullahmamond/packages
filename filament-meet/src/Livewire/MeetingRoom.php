<?php

namespace Atifullahmamond\FilamentMeet\Livewire;

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Component;
use Atifullahmamond\FilamentMeet\Enums\MeetingStatus;
use Atifullahmamond\FilamentMeet\Exceptions\MeetingException;
use Atifullahmamond\FilamentMeet\Models\Meeting;
use Atifullahmamond\FilamentMeet\Services\MeetingService;

class MeetingRoom extends Component
{
    public Meeting $meeting;

    /** @var array<int, array{id: int, name: string, joined_at: ?string}> */
    public array $activeParticipants = [];

    public bool $isMeetingEnded = false;
    public bool $isHost         = false;
    public string $jitsiDomain  = '';
    public string $roomId       = '';
    public string $displayName  = '';
    public ?string $jwtToken    = null;
    public string $userEmail    = '';

    protected MeetingService $meetingService;

    public function boot(MeetingService $meetingService): void
    {
        $this->meetingService = $meetingService;
    }

    public function mount(Meeting $meeting): void
    {
        $user = Auth::user();
        abort_if($user === null, 401);

        // Authorize: throw 403 if not allowed
        abort_unless($this->meetingService->canJoin($meeting, $user), 403, 'You are not authorized to join this meeting.');

        $this->meeting = $meeting;
        $this->isHost  = (int) $meeting->host_id === (int) $user->id;
        $this->jitsiDomain = config('filament-meet.jitsi_domain', 'meet.jit.si');
        $this->roomId      = $meeting->room_id;
        $this->displayName = $user->name ?? $user->email;
        $this->userEmail   = $user->email ?? '';
        $this->jwtToken    = $this->meetingService->generateJitsiJwt($meeting, $user);

        // Record join in DB
        try {
            $this->meetingService->joinMeeting($meeting, $user);
        } catch (MeetingException $e) {
            // Already joined or other non-critical state issue — log and continue
            logger()->warning("MeetingRoom join issue: {$e->getMessage()}");
        }

        $this->meeting->refresh();

        $this->refreshParticipants();
    }

    // -------------------------------------------------------------------------
    // Public Actions (called from Alpine.js / blade)
    // -------------------------------------------------------------------------

    public function startMeeting(): void
    {
        $user = Auth::user();

        abort_unless($this->isHost, 403);
        abort_unless($this->meeting->isScheduled() || $this->meeting->isActive(), 400, 'Cannot start meeting.');

        if ($this->meeting->isScheduled()) {
            try {
                $this->meetingService->startMeeting($this->meeting, $user);
                $this->meeting->refresh();
            } catch (MeetingException $e) {
                $this->dispatch('notify', type: 'error', message: $e->getMessage());
                return;
            }
        }

        $this->dispatch('meeting-started');
    }

    public function endMeeting(): void
    {
        $user = Auth::user();

        abort_unless($this->isHost, 403);

        try {
            $this->meetingService->endMeeting($this->meeting, $user);
            $this->meeting->refresh();
            $this->isMeetingEnded = true;
        } catch (MeetingException $e) {
            $this->dispatch('notify', type: 'error', message: $e->getMessage());
            return;
        }

        $this->dispatch('meeting-ended');
    }

    public function leaveMeeting(): void
    {
        $user = Auth::user();

        try {
            $this->meetingService->leaveMeeting($this->meeting, $user);
        } catch (\Throwable $e) {
            logger()->warning("Leave meeting error: {$e->getMessage()}");
        }

        $this->dispatch('redirect-to-panel', url: filament()->getHomeUrl());
    }

    public function refreshParticipants(): void
    {
        $this->activeParticipants = $this->meeting
            ->participants()
            ->wherePivotNotNull('joined_at')
            ->wherePivotNull('left_at')
            ->get()
            ->map(fn ($u) => [
                'id'        => $u->getKey(),
                'name'      => $u->name ?? $u->email,
                'joined_at' => $u->pivot->joined_at?->format('H:i'),
            ])
            ->values()
            ->toArray();
    }

    // -------------------------------------------------------------------------
    // Echo listeners (real-time participant list refresh)
    // -------------------------------------------------------------------------

    /**
     * Broadcast when meeting becomes active (host started or host joined scheduled room).
     * Participants waiting on the lobby screen load Jitsi when they receive this.
     */
    #[On('echo-presence:meeting.{meeting.uuid},meeting.started')]
    public function onMeetingStartedBroadcast(): void
    {
        if ($this->isMeetingEnded) {
            return;
        }

        $this->meeting->refresh();

        if ($this->meeting->isActive()) {
            $this->dispatch('meeting-started');
        }
    }

    /**
     * Fallback when realtime broadcasting is off or Echo is disconnected.
     */
    public function refreshMeetingWhenWaiting(): void
    {
        if ($this->isMeetingEnded || ! $this->meeting->isScheduled()) {
            return;
        }

        $this->meeting->refresh();

        if ($this->meeting->isActive()) {
            $this->dispatch('meeting-started');
        }
    }

    #[On('echo-presence:meeting.{meeting.uuid},participant.joined')]
    public function onParticipantJoined(): void
    {
        $this->refreshParticipants();
    }

    #[On('echo-presence:meeting.{meeting.uuid},participant.left')]
    public function onParticipantLeft(): void
    {
        $this->refreshParticipants();
    }

    #[On('echo-presence:meeting.{meeting.uuid},meeting.ended')]
    public function onMeetingEnded(): void
    {
        $this->meeting->refresh();
        $this->isMeetingEnded = true;
        $this->dispatch('meeting-ended');
    }

    // -------------------------------------------------------------------------
    // Render
    // -------------------------------------------------------------------------

    public function render(): \Illuminate\View\View
    {
        return view('filament-meet::livewire.meeting-room', [
            'lwComponentId' => $this->getId(),
        ]);
    }
}
