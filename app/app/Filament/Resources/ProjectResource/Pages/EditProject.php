<?php

namespace App\Filament\Resources\ProjectResource\Pages;

use App\Filament\Resources\ProjectResource;
use App\Filament\Resources\ProjectResource\Pages\Concerns\InteractsWithProjectDateEditor;
use App\Models\Role;
use App\Models\User;
use App\Notifications\CustomerCredentialsNotification;
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

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        return ProjectResource::normalizeEventDateFormData($data);
    }

    public function sendCustomerCredentials(string $field): void
    {
        if (! in_array($field, ['reference_email', 'partner_2_reference_email'], true)) {
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
}
