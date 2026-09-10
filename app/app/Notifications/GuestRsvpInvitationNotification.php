<?php

namespace App\Notifications;

use App\Models\Guest;
use App\Models\Project;
use App\Models\Template;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

class GuestRsvpInvitationNotification extends Notification
{
    use Queueable;

    public function __construct(
        protected Project $project,
        protected Guest $guest,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $subject = Str::of($this->renderTemplate($this->subject(), escapeValues: false))
            ->stripTags()
            ->squish()
            ->value();

        return (new MailMessage)
            ->mailer('notify')
            ->from(config('mail.notify_from.address'), config('mail.notify_from.name'))
            ->subject($subject)
            ->view('mail.lead-contract', [
                'bodyHtml' => $this->renderBodyTemplate($this->bodyHtml()),
            ]);
    }

    protected function subject(): string
    {
        return (string) ($this->project->rsvp_invitation_email_subject ?: $this->template()->subject ?: $this->template()->title);
    }

    protected function bodyHtml(): string
    {
        return (string) ($this->project->rsvp_invitation_email_html ?: $this->template()->content);
    }

    protected function template(): Template
    {
        return Template::query()
            ->where('slug', 'mail-rsvp-invitation')
            ->where('language', 'en')
            ->firstOrFail();
    }

    protected function renderTemplate(string $content, bool $escapeValues = true): string
    {
        $websiteUrl = $this->personalWebsiteUrl();
        $contactName = trim(collect([$this->project->first_name, $this->project->last_name])->filter()->implode(' '));

        $values = [
            'guest_names' => $this->guest->displayName(),
            'couple_name' => $this->project->coupleNames() ?: $this->project->name,
            'couple_names' => $this->project->coupleNames() ?: $this->project->name,
            'website_link' => $websiteUrl,
            'rsvp_link' => $this->guest->publicRsvpUrl(),
            'event_date' => $this->eventDate(),
            'rsvp_deadline_date' => $this->rsvpDeadlineDate(),
            'event_location' => $this->project->displayLocationLabel(),
            'contact_name' => $contactName !== '' ? $contactName : $this->project->name,
            'contact_email' => $this->project->email ?: $this->project->secondary_email ?: '',
            'contact_phone' => $this->project->phone ?: $this->project->secondary_phone ?: '',
        ];

        foreach ($values as $key => $value) {
            $value = $escapeValues ? e($value) : $value;

            $content = str_replace([
                '{{ ' . $key . ' }}',
                '{{' . $key . '}}',
            ], $value, $content);
        }

        return $content;
    }

    protected function renderBodyTemplate(string $content): string
    {
        $websiteUrl = $this->personalWebsiteUrl();
        $rsvpUrl = $this->guest->publicRsvpUrl();

        $content = $this->renderTemplate($content);

        foreach ($this->encodedPlaceholderVariants('website_link') as $placeholder) {
            $content = str_replace($placeholder, e($websiteUrl), $content);
        }

        foreach ($this->encodedPlaceholderVariants('rsvp_link') as $placeholder) {
            $content = str_replace($placeholder, e($rsvpUrl), $content);
        }

        $links = [
            e($websiteUrl) => sprintf('<a href="%s">Open wedding website</a>', e($websiteUrl)),
            e($rsvpUrl) => sprintf('<a href="%s">Complete your RSVP</a>', e($rsvpUrl)),
        ];

        foreach ($links as $url => $anchor) {
            if (str_contains($content, 'href="' . $url . '"') || str_contains($content, "href='" . $url . "'")) {
                continue;
            }

            $content = str_replace($url, $anchor, $content);
        }

        return $content;
    }

    protected function encodedPlaceholderVariants(string $key): array
    {
        return [
            rawurlencode('{{ ' . $key . ' }}'),
            rawurlencode('{{' . $key . '}}'),
            str_replace('%20', '+', rawurlencode('{{ ' . $key . ' }}')),
        ];
    }

    protected function personalWebsiteUrl(): string
    {
        return route('public.project-website.rsvp', [
            'projectAlias' => $this->project->alias,
            'rsvpToken' => $this->guest->rsvp_token,
        ]);
    }

    protected function eventDate(): string
    {
        if (! $this->project->event_start_date) {
            return 'Date to be confirmed';
        }

        $date = $this->project->event_start_date->format('F j, Y');

        if ($this->project->event_end_date && ! $this->project->event_start_date->isSameDay($this->project->event_end_date)) {
            $date .= ' - ' . $this->project->event_end_date->format('F j, Y');
        }

        return $date;
    }

    protected function rsvpDeadlineDate(): string
    {
        $eventDate = $this->project->event_start_date ?: $this->project->event_date;

        return $eventDate
            ? $eventDate->copy()->subMonthsNoOverflow(3)->format('F j, Y')
            : 'Date to be confirmed';
    }
}
