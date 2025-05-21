<?php
namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class UnavailabilityResponseNotification extends Notification
{
    use Queueable;

    protected $unavailabilityResponse;

    public function __construct($unavailabilityResponse)
    {
        $this->unavailabilityResponse = $unavailabilityResponse;
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'status'  => $this->unavailabilityResponse['status'],
            'manager' => $this->unavailabilityResponse['manager'],
            'message' => $this->unavailabilityResponse['message'],
        ];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'status'  => $this->unavailabilityResponse['status'],
            'manager' => $this->unavailabilityResponse['manager'],
            'message' => $this->unavailabilityResponse['message'],
        ];
    }
}
