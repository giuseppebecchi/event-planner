<?php

namespace App\Notifications;

use App\Filament\Resources\LeadResource;
use App\Models\Lead;
use Filament\Actions\Action;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SurveyFilledNotification extends Notification
{
    use Queueable;

    public function __construct(
        protected Lead $lead,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Survey Filled')
            ->greeting('Hello ' . ($notifiable->name ?? ''))
            ->line($this->leadName() . ' has completed the lead questionnaire.')
            ->line('Open the lead to review the submitted answers and continue the planning workflow.')
            ->action('Open Lead', $this->leadUrl());
    }

    public function toDatabase(object $notifiable): array
    {
        return FilamentNotification::make()
            ->title('Survey Filled')
            ->body($this->leadName() . ' has completed the lead questionnaire.')
            ->success()
            ->actions([
                Action::make('openLead')
                    ->label('Open Lead')
                    ->url($this->leadUrl())
                    ->markAsRead(),
            ])
            ->getDatabaseMessage();
    }

    protected function leadName(): string
    {
        return $this->lead->couple_name
            ?: trim(collect([$this->lead->first_name, $this->lead->last_name])->filter()->implode(' '))
            ?: $this->lead->email
            ?: 'A lead';
    }

    protected function leadUrl(): string
    {
        return LeadResource::getUrl('edit', ['record' => $this->lead], panel: 'admin');
    }
}
