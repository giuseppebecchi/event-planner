<?php

namespace App\Notifications;

use App\Models\Payment;
use App\Models\Template;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

class PaymentReminderNotification extends Notification
{
    use Queueable;

    public function __construct(
        protected Payment $payment,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $this->payment->loadMissing('project', 'supplier');

        $template = $this->template();
        $subject = Str::of($this->renderTemplate((string) ($template->subject ?: $template->title)))
            ->stripTags()
            ->squish()
            ->value();

        $bodyHtml = collect([
            $this->renderTemplate((string) $template->content),
            $this->renderTemplate((string) $this->template('mail-signature')->content),
        ])
            ->filter(fn (string $content): bool => trim($content) !== '')
            ->implode("\n");

        return (new MailMessage)
            ->subject($subject)
            ->view('mail.lead-contract', [
                'bodyHtml' => $bodyHtml,
            ]);
    }

    protected function template(string $slug = 'mail-reminder-payment'): Template
    {
        return Template::query()
            ->where('slug', $slug)
            ->firstOrFail();
    }

    protected function renderTemplate(string $content): string
    {
        $replacements = [
            'couple_names' => e($this->coupleNames()),
            'couples_name' => e($this->couplesName()),
            'supplier_name' => e($this->payment->supplier?->name ?: 'the supplier'),
            'date' => e($this->payment->due_date?->format('F j, Y') ?: 'the due date'),
        ];

        foreach ($replacements as $key => $value) {
            $content = str_replace([
                '{{ ' . $key . ' }}',
                '{{' . $key . '}}',
            ], $value, $content);
        }

        return $content;
    }

    protected function couplesName(): string
    {
        return $this->coupleNames();
    }

    protected function coupleNames(): string
    {
        $project = $this->payment->project;

        if (! $project) {
            return 'there';
        }

        return $project->coupleNames() ?: $project->name;
    }
}
