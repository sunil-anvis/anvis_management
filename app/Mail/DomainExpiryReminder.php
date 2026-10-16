<?php

namespace App\Mail;

use App\Models\Domain;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DomainExpiryReminder extends Mailable
{
    use Queueable, SerializesModels;

    public $domain;
    public $daysRemaining;

    /**
     * Create a new message instance.
     */
    public function __construct(Domain $domain, $daysRemaining)
    {
        $this->domain = $domain;
        $this->daysRemaining = $daysRemaining;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Domain Expiry Reminder: {$this->domain->name} ({$this->daysRemaining} days left)",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.domains.expiry-reminder',
            with: [
                'domainName' => $this->domain->name,
                'daysRemaining' => $this->daysRemaining,
                'expiryDate' => $this->domain->expiry_date,
            ],
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
