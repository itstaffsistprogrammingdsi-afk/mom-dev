<?php

namespace App\Mail;

use App\Models\Meeting;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class MeetingSharedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Meeting $meeting,
        public ?string $messageText,
        public bool $includePdf,
        public ?string $pdfContent,
        public ?string $pdfFilename,
        public User $sender,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Dokumen dibagikan: '.$this->meeting->title,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.meeting-shared',
            with: [
                'meeting' => $this->meeting,
                'messageText' => $this->messageText,
                'includePdf' => $this->includePdf,
                'senderName' => $this->sender->name,
                'meetingUrl' => route('meetings.show', $this->meeting),
            ],
        );
    }

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        if (! $this->includePdf || ! $this->pdfContent || ! $this->pdfFilename) {
            return [];
        }

        return [
            Attachment::fromData(fn () => $this->pdfContent, $this->pdfFilename)
                ->withMime('application/pdf'),
        ];
    }
}
