<?php
namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Notifications\Notification;

class UnavailabilityNotification extends Notification
{
    use Queueable;

    protected $unavailability;

    public function __construct(array $unavailability)
    {
        $this->unavailability = $unavailability;
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toDatabase($notifiable)
    {
        Log::info('toDatabase method triggered for UnavailabilityNotification');

        return [
            'title'   => 'New Unavailability Request',
            'message' => 'User ' . $this->unavailability['userId'] . ' has submitted an unavailability request.',
            'fromDT'  => $this->unavailability['fromDT'],
            'toDT'    => $this->unavailability['toDT'],
        ];
    }

    public function toArray($notifiable): array
    {
        return $this->toDatabase($notifiable);
    }
}
