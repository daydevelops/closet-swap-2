<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PasswordChangedNotification extends Notification
{
    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $contact = config('app.contact_email', 'hello@closetswap.com');

        return (new MailMessage)
            ->subject('Your password has been changed')
            ->greeting('Hello,')
            ->line('Your Closet Swap password was recently changed.')
            ->line("If you did not make this change, please contact us immediately at {$contact}.")
            ->line('Please do not reply to this email.')
            ->salutation('The Closet Swap team');
    }
}
