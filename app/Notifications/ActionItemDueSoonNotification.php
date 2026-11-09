<?php

namespace App\Notifications;

use App\Models\ActionItem;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ActionItemDueSoonNotification extends Notification implements ShouldQueueAfterCommit
{
    use Queueable;

    public function __construct(
        public ActionItem $actionItem,
        public string $kind = 'h1',
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
        $label = match ($this->kind) {
            'overdue' => 'terlambat',
            'h3' => 'akan jatuh tempo dalam 3 hari',
            default => 'akan jatuh tempo besok',
        };

        return [
            'title' => 'Pengingat action item',
            'message' => "Tugas \"{$this->actionItem->title}\" {$label}.",
            'url' => route('action-items.index'),
            'type' => 'action_item.reminder',
            'action_item_id' => $this->actionItem->id,
            'meeting_id' => $this->actionItem->meeting_id,
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $label = match ($this->kind) {
            'overdue' => 'sudah melewati tenggat',
            'h3' => 'akan jatuh tempo dalam 3 hari',
            default => 'akan jatuh tempo besok',
        };

        return (new MailMessage)
            ->subject('Pengingat: '.$this->actionItem->title)
            ->greeting('Halo '.$notifiable->name.',')
            ->line("Tugas \"{$this->actionItem->title}\" {$label}.")
            ->line('Tenggat: '.($this->actionItem->due_date?->format('d M Y') ?? '-'))
            ->action('Buka action item', route('action-items.index'));
    }
}
