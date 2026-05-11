<?php

namespace Atifullahmamond\FilamentMeet\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Atifullahmamond\FilamentMeet\Enums\MeetingStatus;

class Meeting extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'meetings';

    protected $fillable = [
        'uuid',
        'title',
        'description',
        'room_id',
        'host_id',
        'status',
        'scheduled_at',
        'started_at',
        'ended_at',
        'recording_url',
        'summary',
    ];

    protected $casts = [
        'status'       => MeetingStatus::class,
        'scheduled_at' => 'datetime',
        'started_at'   => 'datetime',
        'ended_at'     => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (Meeting $meeting) {
            if (empty($meeting->uuid)) {
                $meeting->uuid = (string) Str::uuid();
            }
            if (empty($meeting->room_id)) {
                $meeting->room_id = static::generateRoomId($meeting->uuid);
            }
        });
    }

    public static function generateRoomId(string $uuid): string
    {
        $secret = config('app.key');
        $hash   = substr(hash_hmac('sha256', $uuid, $secret), 0, 16);
        return 'fm-' . str_replace('-', '', $uuid) . '-' . $hash;
    }

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function host(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model', \App\Models\User::class), 'host_id');
    }

    public function participants(): BelongsToMany
    {
        return $this->belongsToMany(
            config('auth.providers.users.model', \App\Models\User::class),
            'meeting_user',
            'meeting_id',
            'user_id'
        )->using(MeetingParticipant::class)
            ->withPivot(['joined_at', 'left_at']);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(MeetingLog::class, 'meeting_id');
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function isScheduled(): bool
    {
        return $this->status === MeetingStatus::Scheduled;
    }

    public function isActive(): bool
    {
        return $this->status === MeetingStatus::Active;
    }

    public function isEnded(): bool
    {
        return $this->status === MeetingStatus::Ended;
    }

    public function isCancelled(): bool
    {
        return $this->status === MeetingStatus::Cancelled;
    }

    public function getDurationInMinutes(): ?int
    {
        if ($this->started_at && $this->ended_at) {
            return (int) $this->started_at->diffInMinutes($this->ended_at);
        }
        return null;
    }

    public function getActiveParticipantCount(): int
    {
        return $this->participants()
            ->wherePivotNotNull('joined_at')
            ->wherePivotNull('left_at')
            ->count();
    }
}
