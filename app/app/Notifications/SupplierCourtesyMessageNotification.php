<?php

namespace App\Notifications;

use App\Models\CategoryBudgetSupplier;
use App\Models\Template;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

class SupplierCourtesyMessageNotification extends Notification
{
    use Queueable;

    public function __construct(
        protected CategoryBudgetSupplier $proposal,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $this->proposal->loadMissing('project', 'supplier');

        $template = $this->template();
        $subject = Str::of($this->renderTemplate((string) ($template->subject ?: $template->title)))
            ->stripTags()
            ->squish()
            ->value();

        $bodyHtml = collect([
            $this->renderTemplate((string) $template->content),
            $this->renderTemplate((string) (Template::query()
                ->where('slug', 'mail-signature')
                ->whereIn('language', [$this->language(), 'en', 'it'])
                ->orderByRaw("case when language = ? then 0 when language = 'en' then 1 else 2 end", [$this->language()])
                ->first()?->content ?? '')),
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
            ->where('slug', 'supplier-courtesy-message')
            ->where('language', $this->language())
            ->first()
            ?: Template::query()
                ->where('slug', 'supplier-courtesy-message')
                ->where('language', 'it')
                ->firstOrFail();
    }

    protected function renderTemplate(string $content): string
    {
        $replacements = [
            'supplier_name' => e($this->proposal->supplier?->name ?: 'Supplier'),
            'vendor_name' => e($this->proposal->supplier?->name ?: 'Supplier'),
            'couple_names' => e($this->proposal->project?->coupleNames() ?: $this->proposal->project?->name ?: ''),
            'wedding_reference' => e($this->weddingReference()),
        ];

        foreach ($replacements as $key => $value) {
            $content = str_replace([
                '{{ ' . $key . ' }}',
                '{{' . $key . '}}',
            ], $value, $content);
        }

        return $content;
    }

    protected function language(): string
    {
        return $this->proposal->supplier?->lang_comunication === 'en' ? 'en' : 'it';
    }

    protected function weddingReference(): string
    {
        $project = $this->proposal->project;

        if (! $project) {
            return '';
        }

        $date = $project->event_date?->format('F j, Y')
            ?: $project->event_start_date?->format('F j, Y')
            ?: $project->wedding_period;

        return collect([$project->coupleNames() ?: $project->name, $date])
            ->filter()
            ->implode(' - ');
    }
}
