<?php

namespace App\Notifications;

use App\Models\Project;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CustomerCredentialsNotification extends Notification
{
    use Queueable;

    public function __construct(
        protected Project $project,
        protected string $email,
        protected string $password,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your portal access is ready!')
            ->greeting('Hello ' . ($notifiable->name ?? ''))
            ->line('Your customized portal for the wedding planning is all set and waiting!')
            ->line('Here are your login details:')
            ->line('Email: ' . $this->email)
            ->line('Password: ' . $this->password)
            ->action('Open Portal Link', url('/admin'))
            ->line('For security reasons, we kindly ask you to change your password immediately after your first login.')
            ->line('Enjoy!');
    }
}
