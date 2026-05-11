<?php

namespace Atifullahmamond\FilamentMeet\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;
use Atifullahmamond\FilamentMeet\Enums\MeetingStatus;
use Atifullahmamond\FilamentMeet\Models\Meeting;

class MeetingStatsWidget extends BaseWidget
{
    protected static ?int $sort = -1;

    protected int | string | array $columnSpan = 'full';

    protected function getStats(): array
    {
        $totalMeetings = Meeting::count();

        $activeMeetings = Meeting::where('status', MeetingStatus::Active)->count();

        $participantsToday = DB::table('meeting_user')
            ->whereDate('joined_at', today())
            ->whereNotNull('joined_at')
            ->count();

        $endedInWindow = Meeting::query()
            ->where('status', MeetingStatus::Ended)
            ->whereNotNull('started_at')
            ->whereNotNull('ended_at')
            ->where('started_at', '>=', now()->subDays(30))
            ->get(['started_at', 'ended_at']);

        $avgDurationMinutes = $endedInWindow->isEmpty()
            ? null
            : $endedInWindow->avg(fn (Meeting $m) => $m->started_at->diffInMinutes($m->ended_at));

        $avgDuration = $avgDurationMinutes !== null
            ? round((float) $avgDurationMinutes) . ' min'
            : 'N/A';

        // Weekly trend for total meetings (last 7 days)
        $weeklyTrend = Meeting::query()
            ->where('created_at', '>=', now()->subDays(7))
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('count')
            ->toArray();

        return [
            Stat::make('Total Meetings', number_format($totalMeetings))
                ->description('All time')
                ->descriptionIcon('heroicon-m-video-camera')
                ->chart($weeklyTrend ?: [0])
                ->color('primary'),

            Stat::make('Active Now', number_format($activeMeetings))
                ->description($activeMeetings === 1 ? 'meeting in progress' : 'meetings in progress')
                ->descriptionIcon('heroicon-m-signal')
                ->color($activeMeetings > 0 ? 'success' : 'gray'),

            Stat::make('Participants Today', number_format($participantsToday))
                ->description('Unique joins today')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('info'),

            Stat::make('Avg. Duration (30 days)', $avgDuration)
                ->description('Ended meetings')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),
        ];
    }
}
