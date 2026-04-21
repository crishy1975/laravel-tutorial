<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class MessungenExportMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $fileContent,
        public string $fileName,
        public string $kontrolleurId,
        public int $anzahl,
        public ?string $zeitraum = null,
        public ?string $absenderName = null,
    ) {}

    public function envelope(): Envelope
    {
        $from = config('messungen.amt_email_from');
        $fromName = config('messungen.amt_email_from_name');

        $envelope = new Envelope(
            subject: 'Amt-Export Kaminmessungen — ' . $this->kontrolleurId,
        );

        if ($from) {
            $envelope->from = new \Illuminate\Mail\Mailables\Address($from, $fromName ?? '');
        }

        return $envelope;
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.amt-export',
            with: [
                'kontrolleurId' => $this->kontrolleurId,
                'anzahl' => $this->anzahl,
                'zeitraum' => $this->zeitraum,
                'absenderName' => $this->absenderName,
                'fileName' => $this->fileName,
            ],
        );
    }

    public function attachments(): array
    {
        return [
            Attachment::fromData(fn() => $this->fileContent, $this->fileName)
                ->withMime('text/plain'),
        ];
    }
}
