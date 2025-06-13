<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RosterShiftDeleted extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $shift;

    /**
     * Create a new message instance.
     */
     public function __construct($user, $shift)
    {
        $this->user = $user;
        $this->shift = $shift;
    }

    /**
     * Get the message envelope.
     */
    public function build()
    {
        return $this->subject('Your shift has been removed')
                    ->view('emails.roster_shift_deleted');
    }

    
}
