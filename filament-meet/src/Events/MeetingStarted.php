<?php

namespace Atifullahmamond\FilamentMeet\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Atifullahmamond\FilamentMeet\Models\Meeting;

class MeetingStarted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public readonly Meeting $meeting)
    {
    }

    public function broadcastOn(): array
    {
        return [
            new PresenceChannel("meeting.{$this->meeting->uuid}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'meeting.started';
    }

    public function broadcastWith(): array
    {
        return [
            'meeting_uuid' => $this->meeting->uuid,
            'status'       => $this->meeting->status->value,
            'started_at'   => $this->meeting->started_at?->toISOString(),
        ];
    }
}
