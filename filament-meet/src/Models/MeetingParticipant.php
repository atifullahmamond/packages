<?php

namespace Atifullahmamond\FilamentMeet\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class MeetingParticipant extends Pivot
{
    protected $table = 'meeting_user';

    protected $casts = [
        'joined_at' => 'datetime',
        'left_at'   => 'datetime',
    ];

    public function isCurrentlyInMeeting(): bool
    {
        return $this->joined_at !== null && $this->left_at === null;
    }

    public function getDurationInMinutes(): ?int
    {
        if ($this->joined_at && $this->left_at) {
            return (int) $this->joined_at->diffInMinutes($this->left_at);
        }
        return null;
    }
}
