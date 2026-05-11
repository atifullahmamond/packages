<?php

namespace Atifullahmamond\FilamentMeet\Filament\Resources\MeetingResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Atifullahmamond\FilamentMeet\Filament\Resources\MeetingResource;
use Atifullahmamond\FilamentMeet\Services\MeetingService;

class CreateMeeting extends CreateRecord
{
    protected static string $resource = MeetingResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $participantIds = $data['participant_ids'] ?? [];
        unset($data['participant_ids']);

        /** @var \Atifullahmamond\FilamentMeet\Models\Meeting $meeting */
        $meeting = app(MeetingService::class)->createMeeting(
            array_merge($data, ['participant_ids' => $participantIds]),
            Auth::user()
        );

        return $meeting;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }
}
