<?php

namespace App\Mail;

use App\Models\RoForm;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SendRoToAccountsMail extends Mailable
{
    use Queueable, SerializesModels;

    public $roForm;
    protected $pdfPath;

    public function __construct(RoForm $roForm, string $pdfPath)
    {
        $this->roForm = $roForm;
        $this->pdfPath = $pdfPath;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Service Order - '.$this->roForm->ro_number,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'email.send-ro',
        );
    }

    public function attachments(): array
    {
        return [
            Attachment::fromPath($this->pdfPath)
                ->as($this->roForm->ro_number.'.pdf')
                ->withMime('application/pdf'),
        ];
    }
}
