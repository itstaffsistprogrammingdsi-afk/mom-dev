<?php

namespace App\Notifications;

use App\Models\QuickNote;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class QuickNoteMentionNotification extends Notification implements ShouldQueueAfterCommit
{
    use Queueable;

    public function __construct(public QuickNote $quickNote) {}

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
        return [
            'title' => 'Anda disebut di Quick Note',
            'message' => "{$this->quickNote->creator?->name} menandai Anda pada catatan \"{$this->quickNote->title}\".",
            'url' => route('quick-notes.show', $this->quickNote),
            'type' => 'quick_note.mention',
            'quick_note_id' => $this->quickNote->id,
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Anda disebut di Quick Note: '.$this->quickNote->title)
            ->greeting('Halo '.$notifiable->name.',')
            ->line("{$this->quickNote->creator?->name} menandai Anda pada Quick Note \"{$this->quickNote->title}\".")
            ->line('Silakan buka catatan untuk melihat konteks dan memberikan respons.')
            ->action('Buka Quick Note', route('quick-notes.show', $this->quickNote));
    }
}
