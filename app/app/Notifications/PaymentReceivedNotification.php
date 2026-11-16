<?php

namespace App\Notifications;

use App\Models\Template;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

class PaymentReceivedNotification extends Notification
{
    public function __construct(public array $payload) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $template = Template::query()->where('slug', 'mail-payment-received')->where('language', 'en')->firstOrFail();
        $render = function (string $text): string {
            foreach ($this->payload as $key => $value) {
                $text = str_replace(['{{ '.$key.' }}', '{{'.$key.'}}'], e((string) $value), $text);
            }
            return $text;
        };
        $signature = Template::query()->where('slug', 'mail-signature')->where('language', 'en')->value('content');

        return (new MailMessage)->subject(Str::of($render($template->subject ?: $template->title))->stripTags()->squish()->value())
            ->view('mail.lead-contract', ['bodyHtml' => $render($template->content)."\n".$render($signature ?? '')]);
    }
}
