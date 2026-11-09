<?php

namespace App\Notifications;

use App\Models\ActionItem;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ActionItemAssignedNotification extends Notification implements ShouldQueueAfterCommit
{
    use Queueable;

    public function __construct(public ActionItem $actionItem) {}

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
            'title' => 'Action item baru',
            'message' => "Anda mendapat tugas \"{$this->actionItem->title}\" dari notulen \"{$this->actionItem->meeting?->title}\".",
            'url' => route('action-items.index'),
            'type' => 'action_item.assigned',
            'action_item_id' => $this->actionItem->id,
            'meeting_id' => $this->actionItem->meeting_id,
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $due = $this->actionItem->due_date?->format('d M Y') ?? 'belum ditentukan';

        return (new MailMessage)
            ->subject('Action item baru: '.$this->actionItem->title)
            ->greeting('Halo '.$notifiable->name.',')
            ->line("Anda ditetapkan sebagai PIC untuk \"{$this->actionItem->title}\".")
            ->line('Notulen: '.($this->actionItem->meeting?->title ?? '-'))
            ->line('Tenggat: '.$due)
            ->action('Lihat action item', route('action-items.index'));
    }
}
