<?php
namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
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

    /**
     * Get the mail representation of the notification.
     */
    // public function toMail(object $notifiable): MailMessage
    // {
    //     return (new MailMessage)
    //         ->line('The introduction to the notification.')
    //         ->action('Notification Action', url('/'))
    //         ->line('Thank you for using our application!');
    // }

    public function toDatabase(object $notifiable): array
    {
        return [
            'message' => 'Unavailability notification',
            'data'    => [
                'title'   => $this->unavailability['title'],
                'message' => 'User ' . $this->unavailability['userId'] . ' has submitted an unavailability request.',
                'reason' => $this->unavailability['reason'],
                'fromDT'  => $this->unavailability['fromDT'],
                'toDT'    => $this->unavailability['toDT'],
            ],
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
