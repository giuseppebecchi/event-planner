<?php

namespace App\Filament\Auth;

use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Illuminate\Contracts\Support\Htmlable;

class ResetPassword extends \Filament\Auth\Pages\PasswordReset\ResetPassword
{
    protected function getEmailFormComponent(): Component
    {
        return TextInput::make('email')
            ->label('Email address')
            ->disabled()
            ->autofocus();
    }

    protected function getPasswordFormComponent(): Component
    {
        return parent::getPasswordFormComponent()
            ->label('New password');
    }

    protected function getPasswordConfirmationFormComponent(): Component
    {
        return parent::getPasswordConfirmationFormComponent()
            ->label('Confirm new password');
    }

    public function getResetPasswordFormAction(): Action
    {
        return parent::getResetPasswordFormAction()
            ->label('Reset password');
    }

    public function getTitle(): string | Htmlable
    {
        return 'Reset your password';
    }

    public function getHeading(): string | Htmlable | null
    {
        return 'Reset your password';
    }

    public function getSubheading(): string | Htmlable | null
    {
        return 'Choose a new password for your account.';
    }
}
