<?php
namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class UnavailabilityNotification extends Notification
{
    use Queueable;

    protected $unavailability;

    public function __construct($unavailability)
    {
        //
        $this->unavailability = $unavailability;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
  
    public function via(object $notifiable): array
    {
        return ['database'];
    }


    public function toDatabase(object $notifiable): array
    {
        return [
            'message' => 'Unavailability notification',
            // 'data'    => [
                'title'   => $this->unavailability['title'],
                'message' => ' has submitted ' .$this->unavailability['title'],
                'reason' => $this->unavailability['reason'],
                'fromDT'  => $this->unavailability['fromDT'],
                'toDT'    => $this->unavailability['toDT'],
                'userId' => $this->unavailability['userId'],
                'userName' => $this->unavailability['userName'],
                'unavailabilityId' => $this->unavailability['unavailId'],
                'day'=> $this->unavailability['day'],
                
            // ],
        ];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
