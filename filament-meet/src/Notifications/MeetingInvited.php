<?php

namespace Atifullahmamond\FilamentMeet\Notifications;

use Filament\Actions\Action;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Atifullahmamond\FilamentMeet\Models\Meeting;

class MeetingInvited extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly Meeting $meeting)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $host   = $this->meeting->host;
        $joinUrl = route('filament-meet.room', $this->meeting);

        return (new MailMessage)
            ->subject("You've been invited to: {$this->meeting->title}")
            ->greeting("Hello {$notifiable->name}!")
            ->line("{$host->name} has invited you to join a meeting.")
            ->line("**Meeting:** {$this->meeting->title}")
            ->when(
                $this->meeting->description,
                fn ($m) => $m->line("**Description:** {$this->meeting->description}")
            )
            ->when(
                $this->meeting->scheduled_at,
                fn ($m) => $m->line("**Scheduled:** {$this->meeting->scheduled_at->format('M j, Y \a\t H:i')}")
            )
            ->action('Join Meeting', $joinUrl)
            ->line('If you were not expecting this invitation, you can ignore this email.');
    }

    public function toDatabase(object $notifiable): array
    {
        return FilamentNotification::make()
            ->title("Meeting Invitation: {$this->meeting->title}")
            ->body("You have been invited by {$this->meeting->host->name}.")
            ->icon('heroicon-o-video-camera')
            ->iconColor('success')
            ->actions([
                Action::make('join')
                    ->label('Join')
                    ->url(route('filament-meet.room', $this->meeting)),
            ])
            ->getDatabaseMessage();
    }
}
