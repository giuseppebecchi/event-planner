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
            ->subject('Your Wedding Manager credentials')
            ->greeting('Hello')
            ->line('Your portal access has been prepared for: ' . $this->project->name)
            ->line('Email: ' . $this->email)
            ->line('Password: ' . $this->password)
            ->action('Open portal', url('/admin'))
            ->line('You can change your password after login.');
    }
}
