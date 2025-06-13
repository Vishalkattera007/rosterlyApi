<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class RosterShiftDeleted extends Mailable
{
    use Queueable, SerializesModels;

    public $user, $weeklyShifts, $weekStartDate, $weekEndDate, $deletedDate;

public function __construct($user, $weeklyShifts, $weekStartDate, $weekEndDate, $deletedDate)
{
    $this->user = $user;
    $this->weeklyShifts = $weeklyShifts;
    $this->weekStartDate = $weekStartDate;
    $this->weekEndDate = $weekEndDate;
    $this->deletedDate = $deletedDate;
}


    public function build()
    {
        return $this->subject('Your Updated Weekly Roster')
            ->view('emails.roster_shift_deleted');
    }
}

