<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class UnavailabilitySubmitted extends Notification
{
    use Queueable;

    protected $employee;
    protected $fromDT;
    protected $toDT;

    public function __construct($employee, $fromDT, $toDT)
    {
        $this->employee = $employee;
        $this->fromDT = $fromDT;
        $this->toDT = $toDT;
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toDatabase($notifiable)
    {
        return [
            'message' => "{$this->employee->firstName} requested unavailability from {$this->fromDT} to {$this->toDT}.",
            'employee_id' => $this->employee->id,
            'from' => $this->fromDT,
            'to' => $this->toDT,
        ];
    }
}
