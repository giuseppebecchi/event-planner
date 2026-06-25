<?php

namespace App\Notifications;

use App\Models\Lead;
use App\Models\Template;
use App\Support\LeadContractPdfRenderer;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

class LeadContractNotification extends Notification
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
        $this->lead->loadMissing('project');

        $renderer = app(LeadContractPdfRenderer::class);
        $template = $this->template('mail-contratto');

        $subject = Str::of($this->renderTemplateContent((string) ($template->subject ?: $template->title), $renderer))
            ->stripTags()
            ->squish()
            ->value();

        $bodyHtml = $this->renderTemplateContent((string) $template->content, $renderer);

        return (new MailMessage)
            ->subject($subject)
            ->view('mail.lead-contract', [
                'bodyHtml' => $bodyHtml,
            ])
            ->attachData($renderer->output($this->lead), $renderer->filename($this->lead), [
                'mime' => 'application/pdf',
            ]);
    }

    protected function template(string $slug): Template
    {
        return Template::query()
            ->where('slug', $slug)
            ->firstOrFail();
    }

    protected function renderTemplateContent(string $content, LeadContractPdfRenderer $renderer): string
    {
        return str_replace(
            '[NAME]',
            e($this->leadName()),
            $renderer->replacePlaceholders($content, $this->lead)
        );
    }

    protected function leadName(): string
    {
        $project = $this->lead->project;

        return $this->lead->couple_name
            ?: trim(collect([$this->lead->first_name, $this->lead->last_name])->filter()->implode(' '))
            ?: ($project?->coupleNames() ?: '')
            ?: $this->lead->email
            ?: 'Client';
    }
}
