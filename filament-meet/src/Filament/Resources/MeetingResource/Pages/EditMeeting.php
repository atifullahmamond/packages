<?php

namespace Atifullahmamond\FilamentMeet\Filament\Resources\MeetingResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Atifullahmamond\FilamentMeet\Filament\Resources\MeetingResource;
use Atifullahmamond\FilamentMeet\Notifications\MeetingInvited;

class EditMeeting extends EditRecord
{
    protected static string $resource = MeetingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
            Actions\RestoreAction::make(),
        ];
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $participantIds = $data['participant_ids'] ?? null;
        unset($data['participant_ids']);

        $record->update($data);

        if ($participantIds !== null) {
            $hostId      = $record->host_id;
            $userModel   = config('auth.providers.users.model', \App\Models\User::class);
            $existingIds = $record->participants()->allRelatedIds()->all();

            // Sync participants without detaching the host
            $syncIds = array_unique(array_merge($participantIds, [$hostId]));
            $record->participants()->sync($syncIds);

            $invitedIds = collect($participantIds)
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->diff(collect($existingIds)->map(fn ($id) => (int) $id))
                ->reject(fn (int $id) => $id === (int) $hostId)
                ->values()
                ->all();

            foreach ($invitedIds as $userId) {
                optional($userModel::find($userId))?->notify(new MeetingInvited($record));
            }
        }

        return $record;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }
}
