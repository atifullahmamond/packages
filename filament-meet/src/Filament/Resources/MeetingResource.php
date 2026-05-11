<?php

namespace Atifullahmamond\FilamentMeet\Filament\Resources;

use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\TextSize;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use BackedEnum;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;
use UnitEnum;
use Atifullahmamond\FilamentMeet\Enums\MeetingStatus;
use Atifullahmamond\FilamentMeet\Filament\Resources\MeetingResource\Pages;
use Atifullahmamond\FilamentMeet\Models\Meeting;
use Atifullahmamond\FilamentMeet\Services\MeetingService;

class MeetingResource extends Resource
{
    protected static ?string $model = Meeting::class;

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-video-camera';

    protected static ?string $navigationLabel = 'Meetings';

    protected static string | UnitEnum | null $navigationGroup = 'Communication';

    protected static ?int $navigationSort = 10;

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Schema $schema): Schema
    {
        $userModel = config('auth.providers.users.model', \App\Models\User::class);

        return $schema->components([
            Section::make('Meeting Details')
                ->schema([
                    TextInput::make('title')
                        ->label('Title')
                        ->required()
                        ->maxLength(255)
                        ->columnSpanFull(),

                    Textarea::make('description')
                        ->label('Description')
                        ->rows(3)
                        ->columnSpanFull(),

                    DateTimePicker::make('scheduled_at')
                        ->label('Scheduled At')
                        ->seconds(false)
                        ->nullable(),

                    Select::make('status')
                        ->label('Status')
                        ->options(MeetingStatus::class)
                        ->default(MeetingStatus::Scheduled)
                        ->required()
                        ->hiddenOn('create'),
                ])
                ->columns(2),

            Section::make('Participants')
                ->schema([
                    Select::make('participant_ids')
                        ->label('Invite Participants')
                        ->multiple()
                        ->searchable()
                        ->preload()
                        ->options(function () use ($userModel) {
                            return $userModel::query()
                                ->where('id', '!=', Auth::id())
                                ->orderBy('name')
                                ->pluck('name', 'id');
                        })
                        ->getOptionLabelUsing(fn ($value) => $userModel::find($value)?->name ?? $value)
                        ->columnSpanFull()
                        ->afterStateHydrated(function (Select $component, ?Meeting $record): void {
                            if ($record) {
                                $component->state(
                                    $record->participants()->pluck('id')->all()
                                );
                            }
                        }),
                ]),

            Section::make('Recording & AI')
                ->schema([
                    TextInput::make('recording_url')
                        ->label('Recording URL')
                        ->url()
                        ->nullable(),

                    Textarea::make('summary')
                        ->label('AI Summary')
                        ->rows(4)
                        ->nullable()
                        ->columnSpanFull()
                        ->visible(config('filament-meet.ai_summary_enabled', false)),
                ])
                ->columns(2)
                ->collapsible()
                ->collapsed()
                ->hiddenOn('create'),
        ]);
    }

    public static function table(Table $table): Table
    {
        $userModel = config('auth.providers.users.model', \App\Models\User::class);

        return $table
            ->columns([
                TextColumn::make('title')
                    ->label('Title')
                    ->searchable()
                    ->sortable()
                    ->weight(FontWeight::SemiBold),

                TextColumn::make('host.name')
                    ->label('Host')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('status')
                    ->label('Status')
                    ->sortable()
                    ->badge(),

                TextColumn::make('scheduled_at')
                    ->label('Scheduled At')
                    ->dateTime('M j, Y H:i')
                    ->sortable()
                    ->placeholder('Not scheduled'),

                TextColumn::make('started_at')
                    ->label('Started At')
                    ->dateTime('M j, Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('ended_at')
                    ->label('Ended At')
                    ->dateTime('M j, Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('participants_count')
                    ->label('Participants')
                    ->counts('participants')
                    ->alignCenter(),

                TextColumn::make('created_at')
                    ->label('Created')
                    ->date()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('scheduled_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->options(MeetingStatus::class)
                    ->multiple(),

                SelectFilter::make('host_id')
                    ->label('Host')
                    ->options(fn () => $userModel::query()->orderBy('name')->pluck('name', 'id'))
                    ->searchable(),

                TrashedFilter::make(),
            ])
            ->recordActions([
                Action::make('join')
                    ->label('Join')
                    ->icon('heroicon-o-arrow-right-circle')
                    ->color('success')
                    ->url(fn (Meeting $record): string => route('filament-meet.room', $record))
                    ->openUrlInNewTab(false)
                    ->visible(fn (Meeting $record): bool => app(MeetingService::class)->canJoin($record, Auth::user())),

                ViewAction::make(),
                EditAction::make(),

                Action::make('end')
                    ->label('End Meeting')
                    ->icon('heroicon-o-stop-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (Meeting $record): bool => $record->isActive() && (int) $record->host_id === (int) Auth::id())
                    ->action(function (Meeting $record): void {
                        app(MeetingService::class)->endMeeting($record, Auth::user());
                        Notification::make()->title('Meeting ended.')->success()->send();
                    }),

                DeleteAction::make(),
                RestoreAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Meeting Details')
                ->schema([
                    TextEntry::make('title')
                        ->label('Title')
                        ->size(TextSize::Large)
                        ->weight(FontWeight::Bold),

                    TextEntry::make('host.name')
                        ->label('Host'),

                    TextEntry::make('status')
                        ->label('Status')
                        ->badge(),

                    TextEntry::make('scheduled_at')
                        ->label('Scheduled At')
                        ->dateTime('M j, Y H:i')
                        ->placeholder('Not scheduled'),

                    TextEntry::make('started_at')
                        ->label('Started At')
                        ->dateTime('M j, Y H:i')
                        ->placeholder('Not started'),

                    TextEntry::make('ended_at')
                        ->label('Ended At')
                        ->dateTime('M j, Y H:i')
                        ->placeholder('Ongoing'),

                    TextEntry::make('description')
                        ->label('Description')
                        ->columnSpanFull()
                        ->placeholder('No description'),
                ])
                ->columns(3),

            Section::make('Participants')
                ->schema([
                    RepeatableEntry::make('participants')
                        ->schema([
                            TextEntry::make('name')->label('Name'),
                            TextEntry::make('pivot.joined_at')
                                ->label('Joined')
                                ->dateTime('H:i')
                                ->placeholder('Not joined'),
                            TextEntry::make('pivot.left_at')
                                ->label('Left')
                                ->dateTime('H:i')
                                ->placeholder('Still in'),
                        ])
                        ->columns(3)
                        ->columnSpanFull(),
                ]),

            Section::make('Recording & Summary')
                ->schema([
                    TextEntry::make('recording_url')
                        ->label('Recording')
                        ->url(fn ($state) => $state)
                        ->placeholder('No recording'),

                    TextEntry::make('summary')
                        ->label('AI Summary')
                        ->columnSpanFull()
                        ->placeholder('No summary available'),
                ])
                ->columns(2)
                ->collapsible()
                ->collapsed(),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMeetings::route('/'),
            'create' => Pages\CreateMeeting::route('/create'),
            'view' => Pages\ViewMeeting::route('/{record}'),
            'edit' => Pages\EditMeeting::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([SoftDeletingScope::class])
            ->with(['host', 'participants']);
    }
}
