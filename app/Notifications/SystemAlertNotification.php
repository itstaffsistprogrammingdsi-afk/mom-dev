<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SystemAlertNotification extends Notification implements ShouldQueueAfterCommit
{
    use Queueable;

    public function __construct(
        public string $title,
        public string $message,
        public string $url = '/health',
    ) {
        $this->onQueue('notifications');
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        $channels = ['database'];

        if (method_exists($notifiable, 'wantsEmailNotifications') && $notifiable->wantsEmailNotifications()) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    /**
     * @return array<string, string>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->title,
            'message' => $this->message,
            'url' => url($this->url),
            'type' => 'system.alert',
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Peringatan sistem MoM: '.$this->title)
            ->greeting('Halo '.$notifiable->name.',')
            ->line($this->message)
            ->action('Buka health check', url($this->url));
    }
}
