<?php

namespace App\Notifications;

use App\Models\AiSummary;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AiSummaryReadyNotification extends Notification implements ShouldQueueAfterCommit
{
    use Queueable;

    public function __construct(public AiSummary $summary) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Resume AI selesai',
            'message' => "Resume AI untuk \"{$this->summary->meeting?->title}\" sudah siap ditinjau.",
            'url' => $this->summary->meeting ? route('meetings.show', $this->summary->meeting) : route('dashboard'),
            'type' => 'ai.summary_ready',
            'ai_summary_id' => $this->summary->id,
            'meeting_id' => $this->summary->meeting_id,
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Resume AI siap ditinjau')
            ->line('Resume AI untuk notulen "'.($this->summary->meeting?->title ?? '-').'" sudah selesai.')
            ->action('Tinjau resume', $this->summary->meeting ? route('meetings.show', $this->summary->meeting) : route('dashboard'));
    }
}
