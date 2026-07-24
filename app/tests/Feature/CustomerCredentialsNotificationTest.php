<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\Role;
use App\Models\User;
use App\Notifications\CustomerCredentialsNotification;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

class CustomerCredentialsNotificationTest extends TestCase
{
    use DatabaseTransactions;

    public function test_customer_credentials_email_uses_portal_access_copy(): void
    {
        $project = Project::query()->create([
            'name' => 'Portal wedding',
            'last_name' => 'Client',
        ]);
        $user = User::factory()->create([
            'name' => 'Filomena',
            'email' => 'filomena@example.com',
        ]);

        $mail = (new CustomerCredentialsNotification($project, 'filomena@example.com', 'secret-password'))
            ->toMail($user);

        $this->assertSame('Your portal access is ready!', $mail->subject);
        $this->assertSame('Hello Filomena', $mail->greeting);
        $this->assertContains('Your customized portal for the wedding planning is all set and waiting!', $mail->introLines);
        $this->assertContains('Here are your login details:', $mail->introLines);
        $this->assertContains('Email: filomena@example.com', $mail->introLines);
        $this->assertContains('Password: secret-password', $mail->introLines);
        $this->assertSame('Open Portal Link', $mail->actionText);
        $this->assertSame(url('/admin'), $mail->actionUrl);
        $this->assertContains('For security reasons, we kindly ask you to change your password immediately after your first login.', $mail->outroLines);
        $this->assertContains('Enjoy!', $mail->outroLines);
    }

    public function test_project_credentials_user_uses_contact_name(): void
    {
        Notification::fake();

        $customerRole = Role::query()->firstOrCreate(['name' => Role::CUSTOMER]);
        $project = Project::query()->create([
            'name' => 'Wedding - Internal Code',
            'first_name' => 'Anna',
            'last_name' => 'Rossi',
            'email' => 'anna@example.com',
        ]);

        Livewire::test(\App\Filament\Resources\ProjectResource\Pages\EditProject::class, [
            'record' => $project->getRouteKey(),
        ])->call('sendCustomerCredentials', 'email');

        $this->assertDatabaseHas('users', [
            'email' => 'anna@example.com',
            'name' => 'Anna Rossi',
            'role_id' => $customerRole->id,
        ]);
    }
}
