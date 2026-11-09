<?php

namespace App\Notifications;

use App\Models\Meeting;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MeetingFinalizedNotification extends Notification implements ShouldQueueAfterCommit
{
    use Queueable;

    /**
     * @param  array<int, array{title: string, due_date: ?string}>  $tasks
     */
    public function __construct(
        public Meeting $meeting,
        public array $tasks = [],
    ) {}

    public function via(object $notifiable): array
    {
        $channels = ['database'];

        if (method_exists($notifiable, 'wantsEmailNotifications') && $notifiable->wantsEmailNotifications()) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toArray(object $notifiable): array
    {
        $taskText = count($this->tasks) > 0
            ? ' Anda memiliki '.count($this->tasks).' action item dari rapat ini.'
            : '';

        return [
            'title' => 'Notulen difinalkan',
            'message' => "Notulen \"{$this->meeting->title}\" telah difinalkan.".$taskText,
            'url' => route('meetings.show', $this->meeting),
            'type' => 'meeting.finalized',
            'meeting_id' => $this->meeting->id,
            'confidentiality' => $this->meeting->confidentiality?->value,
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject('Notulen difinalkan: '.$this->meeting->title)
            ->greeting('Halo '.$notifiable->name.',')
            ->line("Notulen \"{$this->meeting->title}\" telah difinalkan dan dapat Anda baca.")
            ->action('Buka notulen', route('meetings.show', $this->meeting));

        if (count($this->tasks) > 0) {
            $mail->line('Action item untuk Anda:');
            foreach ($this->tasks as $task) {
                $mail->line('- '.$task['title'].($task['due_date'] ? ' (tenggat '.$task['due_date'].')' : ''));
            }
        }

        return $mail->line('Label kerahasiaan: '.($this->meeting->confidentiality?->label() ?? '-'));
    }
}
