<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AccountDeletedNotification extends Notification
{
    use Queueable;

    public function __construct(public readonly string $reason) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your Closet Swap account has been removed')
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('Your Closet Swap account has been removed by a moderator.')
            ->line('**Reason:** ' . $this->reason)
            ->line('If you believe this was a mistake, please contact us.');
    }
}
