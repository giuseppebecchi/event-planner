<?php

namespace App\Filament\Resources\ProjectResource\Pages;

use App\Filament\Resources\ProjectResource;
use App\Filament\Resources\ProjectResource\Pages\Concerns\InteractsWithProjectDateEditor;
use App\Models\Role;
use App\Models\User;
use App\Notifications\CustomerCredentialsNotification;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Enums\Width;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Str;

class EditProject extends EditRecord
{
    use InteractsWithProjectDateEditor;

    protected static string $resource = ProjectResource::class;

    protected string $view = 'filament.resources.project-resource.pages.edit-project';

    public static bool $formActionsAreSticky = true;

    protected Width|string|null $maxContentWidth = Width::Full;

    public function getHeading(): string | Htmlable | null
    {
        return null;
    }

    public function getSubheading(): string | Htmlable | null
    {
        return null;
    }

    public function getBreadcrumbs(): array
    {
        return [];
    }

    protected function getHeaderActions(): array
    {
        return [];
    }

    public function sendCustomerCredentialsAction(): Action
    {
        return Action::make('sendCustomerCredentials')
            ->label('Send credentials')
            ->icon('heroicon-o-key')
            ->color('success')
            ->requiresConfirmation()
            ->modalHeading('Send customer portal credentials?')
            ->modalDescription(fn (array $arguments): string => sprintf(
                'Confirm creating or linking a Customer user and sending the portal credentials to %s at %s.',
                $this->customerCredentialsRecipientLabel((string) ($arguments['field'] ?? 'email')),
                $this->customerCredentialsEmailForField((string) ($arguments['field'] ?? 'email')) ?: 'the saved email address'
            ))
            ->modalSubmitActionLabel('Send credentials')
            ->action(function (array $arguments): void {
                $this->sendCustomerCredentials((string) ($arguments['field'] ?? 'email'));
            });
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        return ProjectResource::normalizeEventDateFormData($data);
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['event_spans_multiple_days'] = ProjectResource::eventSpansMultipleDaysFromFormData($data);

        return $data;
    }

    public function sendCustomerCredentials(string $field): void
    {
        if (! in_array($field, ['email', 'secondary_email'], true)) {
            abort(404);
        }

        $project = $this->getRecord();
        $email = trim((string) data_get($project, $field));

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Notification::make()
                ->title('Missing valid email')
                ->body('Save a valid email before sending credentials.')
                ->danger()
                ->send();

            return;
        }

        $customerRole = Role::query()->where('name', Role::CUSTOMER)->firstOrFail();
        $password = Str::password(12);

        $user = User::query()->firstOrNew(['email' => $email]);
        $user->fill([
            'name' => $user->name ?: ($project->name . ' Customer'),
            'role_id' => $user->role_id ?: $customerRole->id,
            'password' => $password,
        ]);
        $user->save();
        $user->projects()->syncWithoutDetaching([$project->id]);
        $user->notify(new CustomerCredentialsNotification($project, $email, $password));

        Notification::make()
            ->title('Credentials sent')
            ->body($email . ' is linked to this event.')
            ->success()
            ->send();
    }

    protected function customerCredentialsEmailForField(string $field): ?string
    {
        if (! in_array($field, ['email', 'secondary_email'], true)) {
            return null;
        }

        $email = trim((string) data_get($this->getRecord(), $field));

        return $email !== '' ? $email : null;
    }

    protected function customerCredentialsRecipientLabel(string $field): string
    {
        return $field === 'secondary_email' ? 'Partner 2' : 'Partner 1';
    }
}
