<?php

namespace Tests\Feature;

use App\Filament\Resources\ProjectResource\Pages\ViewProjectGuests;
use App\Models\Guest;
use App\Models\Project;
use App\Models\Role;
use App\Models\Template;
use App\Models\User;
use App\Notifications\GuestRsvpInvitationNotification;
use Filament\Forms\Components\RichEditor\RichContentRenderer;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

class GuestRsvpInvitationMailTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        Template::query()->updateOrCreate(
            ['slug' => 'mail-rsvp-invitation', 'language' => 'en'],
            [
                'title' => 'RSVP invitation',
                'subject' => 'RSVP for {{ couple_name }}',
                'type' => Template::TYPE_HTML,
                'content' => '<p>Dear {{ guest_names }},</p><p>We are so happy to invite you to celebrate our wedding with us.</p><p>Date: {{ event_date }}<br>RSVP deadline: {{ rsvp_deadline_date }}<br>Location: {{ event_location }}</p><p>{{ website_link }}</p><p>{{ rsvp_link }}</p><p>With love,<br>{{ couple_name }}</p><p>Main contact:<br>{{ contact_name }}<br>{{ contact_email }}<br>{{ contact_phone }}</p>',
            ],
        );
    }

    public function test_project_rsvp_invitation_mail_starts_from_default_template_and_can_be_saved(): void
    {
        $this->actingAsAdmin();

        $project = Project::query()->create([
            'name' => 'RSVP wedding',
            'first_name' => 'Anna',
            'last_name' => 'Rossi',
            'email' => 'anna@example.com',
            'phone' => '+39 333 1234567',
            'secondary_first_name' => 'Luca',
            'secondary_last_name' => 'Bianchi',
            'event_date' => '2026-10-10',
            'event_start_date' => '2026-10-10',
            'event_end_date' => '2026-10-10',
            'locality' => 'Florence',
            'region' => 'Tuscany',
        ]);

        Livewire::test(ViewProjectGuests::class, ['record' => $project->id])
            ->assertSet('showRsvpMailEditor', false)
            ->call('toggleRsvpMailEditor')
            ->assertSet('showRsvpMailEditor', true)
            ->assertFormSet([
                'subject' => 'RSVP for Anna Rossi & Luca Bianchi',
                'body_html' => '<p>Dear {{ guest_names }},</p><p>We are so happy to invite you to celebrate our wedding with us.</p><p>Date: October 10, 2026<br>RSVP deadline: July 10, 2026<br>Location: Florence, Tuscany</p><p>{{ website_link }}</p><p>{{ rsvp_link }}</p><p>With love,<br>Anna Rossi &amp; Luca Bianchi</p><p>Main contact:<br>Anna Rossi<br>anna@example.com<br>+39 333 1234567</p>',
            ], 'rsvpInvitationMailForm')
            ->fillForm([
                'subject' => 'Custom RSVP',
                'body_html' => '<p>Custom body for {{ guest_names }}</p>',
            ], 'rsvpInvitationMailForm')
            ->call('saveRsvpInvitationMail')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'rsvp_invitation_email_subject' => 'Custom RSVP',
            'rsvp_invitation_email_html' => '<p>Custom body for {{ guest_names }}</p>',
        ]);
    }

    public function test_legacy_default_rsvp_mail_without_href_is_replaced_with_new_default_copy(): void
    {
        $this->actingAsAdmin();

        $project = Project::query()->create([
            'name' => 'Legacy RSVP wedding',
            'first_name' => 'Anna',
            'last_name' => 'Rossi',
            'rsvp_invitation_email_subject' => 'RSVP for {{ couple_names }}',
            'rsvp_invitation_email_html' => '<p>We are delighted to share the wedding website. <a target="_blank" rel="noopener noreferrer nofollow">Open wedding website</a>. <a target="_blank" rel="noopener noreferrer nofollow">Complete your RSVP</a>.</p>',
        ]);

        $component = Livewire::test(ViewProjectGuests::class, ['record' => $project->id])
            ->assertFormSet([
                'subject' => 'RSVP for Anna Rossi',
            ], 'rsvpInvitationMailForm');

        $bodyHtml = RichContentRenderer::make($component->get('rsvpMailForm.body_html'))->toHtml();

        $this->assertStringContainsString('{{ website_link }}', $bodyHtml);
        $this->assertStringNotContainsString('We are delighted to share the wedding website', $bodyHtml);
    }

    public function test_single_rsvp_invitation_is_sent_with_notify_mailer_and_custom_from(): void
    {
        Notification::fake();
        config()->set('mail.notify_from.address', 'notify@example.com');
        config()->set('mail.notify_from.name', 'Notify');

        $this->actingAsAdmin();

        $project = Project::query()->create([
            'name' => 'RSVP wedding',
            'first_name' => 'Anna',
            'last_name' => 'Rossi',
            'secondary_first_name' => 'Luca',
            'secondary_last_name' => 'Bianchi',
            'event_date' => '2026-10-10',
            'event_start_date' => '2026-10-10',
            'rsvp_invitation_email_subject' => 'Your RSVP for {{ couple_names }}',
            'rsvp_invitation_email_html' => '<p>Hello {{ guest_names }}</p><p>Deadline: {{ rsvp_deadline_date }}</p><p>{{ website_link }}</p><p>{{ rsvp_link }}</p>',
        ]);
        $guest = Guest::query()->create([
            'project_id' => $project->id,
            'primary_first_name' => 'Marco',
            'primary_last_name' => 'Verdi',
            'email' => 'marco@example.com',
            'invite_sent' => 0,
        ]);

        Livewire::test(ViewProjectGuests::class, ['record' => $project->id])
            ->call('sendGuestRsvpInvitation', $guest->id);

        $this->assertSame(1, (int) $guest->refresh()->invite_sent);
        $this->assertNotNull($guest->refresh()->rsvp_invitation_sent_at);
        $this->assertNull($guest->refresh()->rsvp_invitation_scheduled_at);

        Notification::assertSentOnDemand(GuestRsvpInvitationNotification::class, function ($notification, array $channels, object $notifiable): bool {
            return in_array('mail', $channels, true)
                && $notifiable->routes['mail'] === 'marco@example.com';
        });

        $mail = (new GuestRsvpInvitationNotification($project->refresh(), $guest->refresh()))
            ->toMail((object) []);

        $this->assertSame('notify', $mail->mailer);
        $this->assertSame(['notify@example.com', 'Notify'], $mail->from);
        $this->assertSame('Your RSVP for Anna Rossi & Luca Bianchi', $mail->subject);
        $this->assertStringContainsString('Deadline: July 10, 2026', (string) $mail->viewData['bodyHtml']);
        $this->assertStringContainsString('<a href="' . $guest->publicRsvpUrl() . '">Complete your RSVP</a>', (string) $mail->viewData['bodyHtml']);
        $this->assertStringContainsString('<a href="' . route('public.project-website.rsvp', ['projectAlias' => $project->alias, 'rsvpToken' => $guest->rsvp_token]) . '">Open wedding website</a>', (string) $mail->viewData['bodyHtml']);
    }

    public function test_all_rsvp_invitations_are_queued_only_for_guests_with_valid_email(): void
    {
        Notification::fake();
        $this->actingAsAdmin();

        $project = Project::query()->create([
            'name' => 'Bulk RSVP wedding',
            'last_name' => 'Client',
            'rsvp_invitation_email_subject' => 'RSVP',
            'rsvp_invitation_email_html' => '<p>{{ rsvp_link }}</p>',
        ]);
        $validGuest = Guest::query()->create([
            'project_id' => $project->id,
            'primary_first_name' => 'Valid',
            'email' => 'valid@example.com',
            'invite_sent' => 0,
        ]);
        $invalidGuest = Guest::query()->create([
            'project_id' => $project->id,
            'primary_first_name' => 'Invalid',
            'email' => 'invalid-email',
            'invite_sent' => 0,
        ]);

        Livewire::test(ViewProjectGuests::class, ['record' => $project->id])
            ->call('sendAllGuestRsvpInvitations');

        $this->assertSame(0, (int) $validGuest->refresh()->invite_sent);
        $this->assertNotNull($validGuest->refresh()->rsvp_invitation_scheduled_at);
        $this->assertNull($validGuest->refresh()->rsvp_invitation_sent_at);
        $this->assertSame(0, (int) $invalidGuest->refresh()->invite_sent);
        $this->assertNull($invalidGuest->refresh()->rsvp_invitation_scheduled_at);

        Notification::assertNothingSent();
    }

    public function test_scheduled_rsvp_invitations_command_sends_at_most_ten_per_run(): void
    {
        Notification::fake();

        $project = Project::query()->create([
            'name' => 'Scheduled RSVP wedding',
            'last_name' => 'Client',
            'rsvp_invitation_email_subject' => 'RSVP',
            'rsvp_invitation_email_html' => '<p>{{ rsvp_link }}</p>',
        ]);

        $guests = collect(range(1, 12))->map(fn (int $index): Guest => Guest::query()->create([
            'project_id' => $project->id,
            'primary_first_name' => 'Guest ' . $index,
            'email' => 'guest-' . $index . '@example.com',
            'invite_sent' => 0,
            'rsvp_invitation_scheduled_at' => now(),
        ]));

        $this->artisan('rsvp:send-scheduled-invitations')
            ->assertExitCode(0);

        $this->assertSame(10, $guests->filter(fn (Guest $guest): bool => (int) $guest->refresh()->invite_sent === 1)->count());
        $this->assertSame(2, $guests->filter(fn (Guest $guest): bool => (int) $guest->refresh()->invite_sent === 0)->count());

        Notification::assertSentOnDemandTimes(GuestRsvpInvitationNotification::class, 10);
    }

    public function test_customer_can_configure_and_send_project_rsvp_invitations(): void
    {
        Notification::fake();

        $customerRole = Role::query()->firstOrCreate(['name' => Role::CUSTOMER]);
        $customer = User::factory()->create(['role_id' => $customerRole->id]);

        $project = Project::query()->create([
            'name' => 'Customer RSVP wedding',
            'last_name' => 'Client',
            'rsvp_invitation_email_subject' => 'Old RSVP',
            'rsvp_invitation_email_html' => '<p>Old body</p>',
        ]);
        $project->users()->attach($customer);

        $guest = Guest::query()->create([
            'project_id' => $project->id,
            'primary_first_name' => 'Guest',
            'email' => 'guest@example.com',
            'invite_sent' => 0,
        ]);

        $this->actingAs($customer);

        Livewire::test(ViewProjectGuests::class, ['record' => $project->id])
            ->assertSee('Personalize RSVP Mail')
            ->assertSee('Send all RSVP')
            ->call('toggleRsvpMailEditor')
            ->assertSet('showRsvpMailEditor', true)
            ->assertSee(route('public.project-rsvp.lookup', ['projectAlias' => $project->alias]))
            ->fillForm([
                'subject' => 'Customer RSVP',
                'body_html' => '<p>Customer body for {{ guest_names }}</p>',
            ], 'rsvpInvitationMailForm')
            ->call('saveRsvpInvitationMail')
            ->assertHasNoFormErrors()
            ->call('sendAllGuestRsvpInvitations');

        $this->assertSame(0, (int) $guest->refresh()->invite_sent);
        $this->assertNotNull($guest->refresh()->rsvp_invitation_scheduled_at);
        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'rsvp_invitation_email_subject' => 'Customer RSVP',
            'rsvp_invitation_email_html' => '<p>Customer body for {{ guest_names }}</p>',
        ]);

        Notification::assertNothingSent();
    }

    public function test_customer_can_update_guest_phone_and_email_from_guest_list(): void
    {
        $customerRole = Role::query()->firstOrCreate(['name' => Role::CUSTOMER]);
        $customer = User::factory()->create(['role_id' => $customerRole->id]);

        $project = Project::query()->create([
            'name' => 'Inline contacts wedding',
            'last_name' => 'Client',
        ]);
        $project->users()->attach($customer);

        $guest = Guest::query()->create([
            'project_id' => $project->id,
            'primary_first_name' => 'Guest',
            'phone' => '111',
            'email' => 'old@example.com',
        ]);

        $this->actingAs($customer);

        Livewire::test(ViewProjectGuests::class, ['record' => $project->id])
            ->set("guestContactForms.{$guest->id}.phone", '+39 333 1234567')
            ->call('saveGuestContact', $guest->id, 'phone')
            ->set("guestContactForms.{$guest->id}.email", 'new@example.com')
            ->call('saveGuestContact', $guest->id, 'email');

        $this->assertDatabaseHas('guests', [
            'id' => $guest->id,
            'phone' => '+39 333 1234567',
            'email' => 'new@example.com',
        ]);
    }

    protected function actingAsAdmin(): void
    {
        $adminRole = Role::query()->firstOrCreate(['name' => Role::ADMIN]);

        $this->actingAs(User::factory()->create(['role_id' => $adminRole->id]));
    }
}
