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
        $subjectTemplate = $this->template('mail-contratto-oggetto');
        $bodyTemplate = $this->template('mail-contratto-corpo');
        $signatureTemplate = $this->template('mail-signature');

        $subject = Str::of($this->renderTemplateContent((string) $subjectTemplate->content, $renderer))
            ->stripTags()
            ->squish()
            ->value();

        $bodyHtml = collect([
            $this->renderTemplateContent((string) $bodyTemplate->content, $renderer),
            $this->renderTemplateContent((string) $signatureTemplate->content, $renderer),
        ])
            ->filter(fn (string $content): bool => trim($content) !== '')
            ->implode("\n");

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
            ?: trim(collect([$project?->partner_one_name, $project?->partner_two_name])->filter()->implode(' & '))
            ?: $this->lead->email
            ?: 'Client';
    }
}
