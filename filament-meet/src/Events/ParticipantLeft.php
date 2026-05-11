<?php

namespace Atifullahmamond\FilamentMeet\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Auth\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Atifullahmamond\FilamentMeet\Models\Meeting;

class ParticipantLeft implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Meeting $meeting,
        public readonly User $user
    ) {
    }

    public function broadcastOn(): array
    {
        return [
            new PresenceChannel("meeting.{$this->meeting->uuid}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'participant.left';
    }

    public function broadcastWith(): array
    {
        return [
            'user_id'  => $this->user->getKey(),
            'left_at'  => now()->toISOString(),
        ];
    }
}
