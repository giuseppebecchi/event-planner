<?php

namespace App\Notifications;

use App\Models\Lead;
use App\Models\Template;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

class LeadFollowUpNotification extends Notification
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
            ->where('language', 'en')
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
            ->where('slug', 'mail-lead-follow-up')
            ->where('language', 'en')
            ->firstOrFail();
    }

    protected function renderTemplate(string $content): string
    {
        $replacements = [
            'couple_names' => e($this->coupleNames()),
            'client_names' => e($this->coupleNames()),
        ];

        foreach ($replacements as $key => $value) {
            $content = str_replace([
                '{{ ' . $key . ' }}',
                '{{' . $key . '}}',
            ], $value, $content);
        }

        return $content;
    }

    protected function coupleNames(): string
    {
        return $this->lead->couple_name
            ?: trim(collect([$this->lead->first_name, $this->lead->last_name])->filter()->implode(' '))
            ?: $this->lead->email
            ?: 'there';
    }
}
