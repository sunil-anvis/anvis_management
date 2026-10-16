<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DomainExpiryMail extends Mailable
{
    use Queueable, SerializesModels;

    public $domain;
    public $daysLeft;
    public $logo;

    /**
     * Create a new message instance.
     */
    public function __construct(\App\Models\Domain $domain, int $daysLeft)
    {
        $this->domain = $domain;
        $this->daysLeft = $daysLeft;
        $this->logo = public_path('anvis-favicon.png');
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Domain Expiry Alert: ' . $this->domain->name . ' (' . $this->daysLeft . ' days left)',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.domain_expiry',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
