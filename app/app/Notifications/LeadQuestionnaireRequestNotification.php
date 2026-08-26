<?php

namespace App\Notifications;

use App\Models\Lead;
use App\Models\Template;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

class LeadQuestionnaireRequestNotification extends Notification
{
    use Queueable;

    public function __construct(
        protected Lead $lead,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $template = $this->template();
        $signature = Template::query()
            ->where('slug', 'mail-signature')
            ->first();

        $subject = Str::of($this->renderTemplate((string) ($template->subject ?: $template->title)))
            ->stripTags()
            ->squish()
            ->value();

        $bodyHtml = collect([
            $this->renderTemplate((string) $template->content),
            $signature ? $this->renderTemplate((string) $signature->content) : '',
        ])
            ->filter(fn (string $content): bool => trim($content) !== '')
            ->implode("\n");

        return (new MailMessage)
            ->subject($subject)
            ->view('mail.lead-contract', [
                'bodyHtml' => $bodyHtml,
            ]);
    }

    protected function template(): Template
    {
        return Template::query()
            ->where('slug', 'mail-lead-questionnaire')
            ->where('language', 'en')
            ->firstOrFail();
    }

    protected function renderTemplate(string $content): string
    {
        $replacements = [
            'client_names' => e($this->clientNames()),
            'questionnaire_link' => e($this->lead->public_form_url),
        ];

        foreach ($replacements as $key => $value) {
            $content = str_replace([
                '{{ ' . $key . ' }}',
                '{{' . $key . '}}',
            ], $value, $content);
        }

        return $content;
    }

    protected function clientNames(): string
    {
        return $this->lead->couple_name
            ?: trim(collect([$this->lead->first_name, $this->lead->last_name])->filter()->implode(' '))
            ?: $this->lead->email
            ?: 'there';
    }
}
