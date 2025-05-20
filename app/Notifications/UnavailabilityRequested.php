<?php
namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class UnavailabilityRequested extends Notification
{
    use Queueable;

    protected $employee;
    protected $from;
    protected $to;

    public function __construct($employee, $from, $to)
    {
        $this->employee = $employee;
        $this->from     = $from;
        $this->to       = $to;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->line('The introduction to the notification.')
            ->action('Notification Action', url('/'))
            ->line('Thank you for using our application!');
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'message'     => "{$this->employee->name} requested unavailability from {$this->from} to {$this->to}",
            'employee_id' => $this->employee->id,
            'from'        => $this->from,
            'to'          => $this->to,
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
