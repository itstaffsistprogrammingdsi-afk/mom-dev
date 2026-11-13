<?php

namespace App\Notifications;

use App\Models\ActionItem;
use App\Models\ActionItemFeedback;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

class ActionItemResponseNotification extends Notification implements ShouldQueueAfterCommit
{
    use Queueable;

    public function __construct(
        public ActionItem $actionItem,
        public ActionItemFeedback $feedback,
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
            'title' => 'Respons action item',
            'message' => "{$this->responder->name} merespons tugas \"{$this->actionItem->title}\" pada dokumen \"{$this->actionItem->meeting?->title}\".",
            'url' => route('meetings.show', $this->actionItem->meeting_id),
            'type' => 'action_item.response',
            'action_item_id' => $this->actionItem->id,
            'meeting_id' => $this->actionItem->meeting_id,
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Respons baru pada action item: '.$this->actionItem->title)
            ->greeting('Halo '.$notifiable->name.',')
            ->line("{$this->responder->name} merespons tugas \"{$this->actionItem->title}\" pada dokumen \"{$this->actionItem->meeting?->title}\".")
            ->line('Status respons: '.($this->feedback->response_status?->label() ?? '-'))
            ->line('Respons: '.Str::limit($this->feedback->body, 280))
            ->action('Buka dokumen', route('meetings.show', $this->actionItem->meeting_id));
    }
}
