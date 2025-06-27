<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SendNotificationsMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $title;
    public string $userName;
    public ?string $fromDT;
    public ?string $toDT;
    public string $reason;
    public ?string $day;

    /**
     * Create a new message instance.
     */
    public function __construct(array $data)
    {
        $this->title    = $data['title'];
        $this->userName = $data['userName'];
        $this->fromDT   = $data['fromDT'] ?? null;
        $this->toDT     = $data['toDT'] ?? null;
        $this->reason   = $data['reason'] ?? 'No reason provided';
        $this->day      = $data['day'] ?? null;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Rosterly Unavailability Notification',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.send_notification',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}

