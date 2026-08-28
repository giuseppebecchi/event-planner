<?php

namespace App\Filament\Auth;

use App\Models\User;
use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use Filament\Actions\Action;
use Filament\Auth\Notifications\ResetPassword as ResetPasswordNotification;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Models\Contracts\FilamentUser;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Component;
use Illuminate\Auth\Events\PasswordResetLinkSent;
use Illuminate\Contracts\Auth\CanResetPassword;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Password;
use LogicException;

class RequestPasswordReset extends \Filament\Auth\Pages\PasswordReset\RequestPasswordReset
{
    public function request(): void
    {
        try {
            $this->rateLimit(2);
        } catch (TooManyRequestsException $exception) {
            $this->getRateLimitedNotification($exception)?->send();

            return;
        }

        $data = $this->form->getState();

        $status = Password::broker(Filament::getAuthPasswordBroker())->sendResetLink(
            $this->getCredentialsFromFormData($data),
            function (CanResetPassword $user, string $token): void {
                if (
                    ($user instanceof FilamentUser) &&
                    (! $user->canAccessPanel(Filament::getCurrentOrDefaultPanel()))
                ) {
                    return;
                }

                if (! method_exists($user, 'notifyNow')) {
                    $userClass = $user::class;

                    throw new LogicException("Model [{$userClass}] does not have a [notifyNow()] method.");
                }

                $notification = app(ResetPasswordNotification::class, ['token' => $token]);
                $notification->url = Filament::getResetPasswordUrl($token, $user);

                $user->notifyNow($notification);

                if (class_exists(PasswordResetLinkSent::class)) {
                    event(new PasswordResetLinkSent($user));
                }
            },
        );

        if ($status !== Password::RESET_LINK_SENT) {
            $this->getFailureNotification($status)?->send();

            return;
        }

        $this->getSentNotification($status)?->send();

        $this->form->fill();
    }

    protected function getEmailFormComponent(): Component
    {
        return TextInput::make('login')
            ->label('Email address or username')
            ->placeholder('Email address or username')
            ->required()
            ->autocomplete()
            ->autofocus();
    }

    protected function getRequestFormAction(): Action
    {
        return parent::getRequestFormAction()
            ->label('Send reset instructions');
    }

    public function loginAction(): Action
    {
        return parent::loginAction()
            ->label('Back to sign in');
    }

    public function getTitle(): string | Htmlable
    {
        return 'Forgot your password?';
    }

    public function getHeading(): string | Htmlable | null
    {
        return 'Forgot your password?';
    }

    public function getSubheading(): string | Htmlable | null
    {
        return "Enter your email address or username and we'll send you a link to reset your password.";
    }

    protected function getFormActions(): array
    {
        return [
            $this->getRequestFormAction(),
            $this->loginAction(),
        ];
    }

    protected function getCredentialsFromFormData(array $data): array
    {
        $login = trim((string) ($data['login'] ?? ''));

        $user = User::query()
            ->where('email', $login)
            ->orWhere('name', $login)
            ->first();

        return [
            'email' => $user?->email ?? $login,
        ];
    }
}
