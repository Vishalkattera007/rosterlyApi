<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\RosterModel;

class RosterAssigned extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $weekStartDate;
    public $weekEndDate;
    public $weeklyShifts;

    public function __construct($user, $weekStartDate, $weekEndDate, $weeklyShifts)
    {
        $this->user          = $user;
        $this->weekStartDate = $weekStartDate;
        $this->weekEndDate   = $weekEndDate;
        $this->weeklyShifts  = $weeklyShifts;
    }

    public function build()
    {
        return $this->subject('Your Weekly Roster Assigned')
                    ->view('emails.roster-assigned');
    }

}

