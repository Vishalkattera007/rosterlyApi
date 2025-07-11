<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SendForgotMail extends Mailable
{
    use Queueable, SerializesModels;

    public $password;
    public $firstName;
    /**
     * Create a new message instance.
     */
    public function __construct($password, $firstName)
    {
        $this->password = $password;
        $this->firstName = $firstName;

    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Forgot Password',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.send_password',
             with: [
            'password' => $this->password,
            'firstName' => $this->firstName,
        ],
        );
    }

    /**
     * Get the attachments for the message.
     */
    public function attachments(): array
    {
        return [];
    }
}

