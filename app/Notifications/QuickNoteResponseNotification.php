<?php

namespace App\Notifications;

use App\Models\QuickNote;
use App\Models\QuickNoteMention;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class QuickNoteResponseNotification extends Notification implements ShouldQueueAfterCommit
{
    use Queueable;

    public function __construct(
        public QuickNote $quickNote,
        public QuickNoteMention $mention,
        public User $responder,
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
        return [
            'title' => 'Respons Quick Note',
            'message' => "{$this->responder->name} merespons catatan \"{$this->quickNote->title}\".",
            'url' => route('quick-notes.show', $this->quickNote),
            'type' => 'quick_note.response',
            'quick_note_id' => $this->quickNote->id,
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Respons baru pada Quick Note: '.$this->quickNote->title)
            ->greeting('Halo '.$notifiable->name.',')
            ->line("{$this->responder->name} merespons Quick Note \"{$this->quickNote->title}\".")
            ->line('Status respons: '.($this->mention->response_status?->label() ?? '-'))
            ->action('Lihat Quick Note', route('quick-notes.show', $this->quickNote));
    }
}
