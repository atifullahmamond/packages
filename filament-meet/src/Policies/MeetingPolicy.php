<?php

namespace Atifullahmamond\FilamentMeet\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User;
use Atifullahmamond\FilamentMeet\Models\Meeting;

class MeetingPolicy
{
    use HandlesAuthorization;

    /**
     * Admins bypass policy checks except {@see create()}, which stays explicit.
     */
    public function before(User $user, string $ability): bool|null
    {
        if ($ability === 'create') {
            return null;
        }

        if ($this->isAdministrator($user)) {
            return true;
        }

        return null;
    }

    /**
     * Determine whether the user can view any meetings.
     * Hosts see their own; participants see assigned ones.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the meeting.
     */
    public function view(User $user, Meeting $meeting): bool
    {
        if ($this->isHost($user, $meeting) || $this->isParticipant($user, $meeting)) {
            return true;
        }

        if (! config('filament-meet.open_join_for_authenticated_users', true)) {
            return false;
        }

        return $meeting->isScheduled() || $meeting->isActive();
    }

    /**
     * Determine whether the user can create meetings.
     * Only administrators (Spatie role `admin`, or boolean `is_admin` on the user model).
     */
    public function create(User $user): bool
    {
        return $this->isAdministrator($user);
    }

    /**
     * Determine whether the user can update the meeting.
     * Only the host (or admin, handled by before()) can update.
     */
    public function update(User $user, Meeting $meeting): bool
    {
        return $this->isHost($user, $meeting)
            && ! $meeting->isEnded()
            && ! $meeting->isCancelled();
    }

    /**
     * Determine whether the user can delete the meeting.
     * Only the host can delete their own meeting (if not active/ended).
     */
    public function delete(User $user, Meeting $meeting): bool
    {
        return $this->isHost($user, $meeting)
            && ! $meeting->isActive()
            && ! $meeting->isEnded();
    }

    /**
     * Determine whether the user can restore the meeting.
     */
    public function restore(User $user, Meeting $meeting): bool
    {
        return $this->isHost($user, $meeting);
    }

    /**
     * Determine whether the user can permanently delete the meeting.
     */
    public function forceDelete(User $user, Meeting $meeting): bool
    {
        return false; // Admin only, handled via before()
    }

    /**
     * Determine whether the user can join the meeting room.
     * Delegates to {@see MeetingService::canJoin()} (host, invitees, optional open join).
     */
    public function join(User $user, Meeting $meeting): bool
    {
        return app(\Atifullahmamond\FilamentMeet\Services\MeetingService::class)->canJoin($meeting, $user);
    }

    /**
     * Determine whether the user can start the meeting.
     * Only the host can start their own meeting.
     */
    public function start(User $user, Meeting $meeting): bool
    {
        return $this->isHost($user, $meeting)
            && $meeting->isScheduled();
    }

    /**
     * Determine whether the user can end the meeting.
     * Only the host can end an active meeting.
     */
    public function end(User $user, Meeting $meeting): bool
    {
        return $this->isHost($user, $meeting)
            && $meeting->isActive();
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    protected function isAdministrator(User $user): bool
    {
        if (method_exists($user, 'hasRole') && $user->hasRole('admin')) {
            return true;
        }

        return (bool) data_get($user, 'is_admin', false);
    }

    protected function isHost(User $user, Meeting $meeting): bool
    {
        return (int) $meeting->host_id === (int) $user->getKey();
    }

    protected function isParticipant(User $user, Meeting $meeting): bool
    {
        return $meeting->participants()
            ->where('user_id', $user->getKey())
            ->exists();
    }
}
