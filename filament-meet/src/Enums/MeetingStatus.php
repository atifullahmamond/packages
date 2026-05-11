<?php

namespace Atifullahmamond\FilamentMeet\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum MeetingStatus: string implements HasColor, HasIcon, HasLabel
{
    case Scheduled = 'scheduled';
    case Active    = 'active';
    case Ended     = 'ended';
    case Cancelled = 'cancelled';

    public function getLabel(): string
    {
        return match ($this) {
            self::Scheduled => 'Scheduled',
            self::Active    => 'Active',
            self::Ended     => 'Ended',
            self::Cancelled => 'Cancelled',
        };
    }

    public function getColor(): string | array | null
    {
        return match ($this) {
            self::Scheduled => 'info',
            self::Active    => 'success',
            self::Ended     => 'gray',
            self::Cancelled => 'danger',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::Scheduled => 'heroicon-o-clock',
            self::Active    => 'heroicon-o-video-camera',
            self::Ended     => 'heroicon-o-check-circle',
            self::Cancelled => 'heroicon-o-x-circle',
        };
    }

    public function canTransitionTo(self $new): bool
    {
        return match ($this) {
            self::Scheduled => in_array($new, [self::Active, self::Cancelled]),
            self::Active    => in_array($new, [self::Ended]),
            self::Ended     => false,
            self::Cancelled => false,
        };
    }
}
