<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Queue\SerializesModels;

class RechnungMail extends Mailable
{
    use Queueable, SerializesModels;

    public $rechnung;
    public $betreff;
    public $nachricht;
    public $mailAttachments;

    /**
     * Create a new message instance.
     */
    public function __construct(array $data)
    {
        $this->rechnung = $data['rechnung'];
        $this->betreff = $data['betreff'];
        $this->nachricht = $data['nachricht'] ?? '';
        $this->mailAttachments = $data['attachments'] ?? [];
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->betreff,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.rechnung',
            with: [
                'rechnung' => $this->rechnung,
                'nachricht' => $this->nachricht,
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        $attachments = [];
        
        foreach ($this->mailAttachments as $attachment) {
            if (isset($attachment['path']) && file_exists($attachment['path'])) {
                $attachments[] = Attachment::fromPath($attachment['path'])
                    ->as($attachment['name'] ?? basename($attachment['path']))
                    ->withMime($attachment['mime'] ?? 'application/octet-stream');
            }
        }
        
        return $attachments;
    }
}