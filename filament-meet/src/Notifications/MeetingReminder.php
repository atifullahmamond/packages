<?php

namespace Atifullahmamond\FilamentMeet\Notifications;

use Filament\Actions\Action;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Atifullahmamond\FilamentMeet\Models\Meeting;

class MeetingReminder extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Meeting $meeting,
        public readonly int $minutesBefore = 15
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $joinUrl = route('filament-meet.room', $this->meeting);

        return (new MailMessage)
            ->subject("Reminder: {$this->meeting->title} starts in {$this->minutesBefore} minutes")
            ->greeting("Hi {$notifiable->name}!")
            ->line("This is a reminder that your meeting is starting soon.")
            ->line("**Meeting:** {$this->meeting->title}")
            ->when(
                $this->meeting->scheduled_at,
                fn ($m) => $m->line("**Starting at:** {$this->meeting->scheduled_at->format('H:i')}")
            )
            ->action('Join Now', $joinUrl)
            ->line('Be sure to join on time!');
    }

    public function toDatabase(object $notifiable): array
    {
        return FilamentNotification::make()
            ->title("Starting soon: {$this->meeting->title}")
            ->body("Meeting starts in {$this->minutesBefore} minutes.")
            ->icon('heroicon-o-clock')
            ->iconColor('warning')
            ->actions([
                Action::make('join')
                    ->label('Join')
                    ->url(route('filament-meet.room', $this->meeting)),
            ])
            ->getDatabaseMessage();
    }
}
