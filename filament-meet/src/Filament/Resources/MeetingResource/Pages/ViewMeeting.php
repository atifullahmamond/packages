<?php

namespace Atifullahmamond\FilamentMeet\Filament\Resources\MeetingResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Auth;
use Atifullahmamond\FilamentMeet\Filament\Resources\MeetingResource;
use Atifullahmamond\FilamentMeet\Services\MeetingService;

class ViewMeeting extends ViewRecord
{
    protected static string $resource = MeetingResource::class;

    protected function getHeaderActions(): array
    {
        $meeting = $this->getRecord();
        $service = app(MeetingService::class);

        return [
            Actions\Action::make('join')
                ->label('Join Meeting')
                ->icon('heroicon-o-arrow-right-circle')
                ->color('success')
                ->size('lg')
                ->url(route('filament-meet.room', $meeting))
                ->visible($service->canJoin($meeting, Auth::user())),

            Actions\EditAction::make(),

            Actions\Action::make('end')
                ->label('End Meeting')
                ->icon('heroicon-o-stop-circle')
                ->color('danger')
                ->requiresConfirmation()
                ->visible($meeting->isActive() && (int) $meeting->host_id === (int) Auth::id())
                ->action(function () use ($meeting, $service) {
                    $service->endMeeting($meeting, Auth::user());
                    $this->refreshFormData(['status', 'ended_at']);
                }),

            Actions\DeleteAction::make(),
        ];
    }
}
