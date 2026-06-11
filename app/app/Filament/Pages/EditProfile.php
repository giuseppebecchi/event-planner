<?php

namespace App\Filament\Pages;

use Filament\Auth\Pages\EditProfile as BaseEditProfile;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;

class EditProfile extends BaseEditProfile
{
    protected Width | string | null $maxWidth = Width::ThreeExtraLarge;

    public function defaultForm(Schema $schema): Schema
    {
        return parent::defaultForm($schema)
            ->inlineLabel(false);
    }

    public function getFormContentComponent(): Component
    {
        /** @var Form $form */
        $form = parent::getFormContentComponent();

        return $form->extraAttributes([
            'style' => 'width: 100%; max-width: 56rem; margin-inline: auto;',
        ], merge: true);
    }

    protected function getEmailFormComponent(): Component
    {
        return parent::getEmailFormComponent()
            ->readOnly()
            ->dehydrated(false);
    }

    protected function getPasswordFormComponent(): Component
    {
        return parent::getPasswordFormComponent()
            ->label('Nuova password')
            ->required(fn (Get $get): bool => filled($get('passwordConfirmation')) || filled($get('currentPassword')));
    }

    protected function getPasswordConfirmationFormComponent(): Component
    {
        return parent::getPasswordConfirmationFormComponent()
            ->label('Conferma nuova password')
            ->visible()
            ->required(fn (Get $get): bool => filled($get('password')));
    }

    protected function getCurrentPasswordFormComponent(): Component
    {
        return parent::getCurrentPasswordFormComponent()
            ->label('Vecchia password')
            ->visible()
            ->required(fn (Get $get): bool => filled($get('password')));
    }
}
