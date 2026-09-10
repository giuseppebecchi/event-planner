<?php

namespace Tests\Feature;

use App\Models\Guest;
use App\Models\Project;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class PublicProjectWebsiteRsvpLinkTest extends TestCase
{
    use DatabaseTransactions;

    public function test_event_website_without_rsvp_token_keeps_rsvp_section_without_personal_link(): void
    {
        $alias = 'test-tecnico-no-rsvp-' . str()->random(8);
        $token = str()->random(40);

        $project = Project::query()->create([
            'name' => 'Test tecnico',
            'alias' => $alias,
            'last_name' => 'Client',
        ]);

        Guest::query()->create([
            'project_id' => $project->id,
            'primary_first_name' => 'Guest',
            'rsvp_token' => $token,
        ]);

        $this->get('/event/' . $alias)
            ->assertOk()
            ->assertSee('RSVP')
            ->assertSee('Find your RSVP')
            ->assertSee(route('public.project-rsvp.lookup', ['projectAlias' => $project->alias]))
            ->assertDontSee('Open RSVP form');
    }

    public function test_event_website_with_rsvp_token_shows_link_to_guest_rsvp_form(): void
    {
        $alias = 'test-tecnico-rsvp-' . str()->random(8);
        $token = str()->random(40);

        $project = Project::query()->create([
            'name' => 'Test tecnico',
            'alias' => $alias,
            'last_name' => 'Client',
        ]);

        $guest = Guest::query()->create([
            'project_id' => $project->id,
            'primary_first_name' => 'Guest',
            'rsvp_token' => $token,
        ]);

        $this->get('/event/' . $alias . '/rsvp/' . $token)
            ->assertOk()
            ->assertSee('Dear Guest please use the following personal RSVP link.')
            ->assertDontSee('Please use the personal RSVP link you received with your invitation.')
            ->assertSee('Open RSVP form')
            ->assertSee($guest->publicRsvpUrl());
    }

    public function test_project_rsvp_lookup_redirects_to_matching_guest_rsvp_by_email(): void
    {
        $alias = 'test-tecnico-lookup-email-' . str()->random(8);
        $token = str()->random(40);

        $project = Project::query()->create([
            'name' => 'Test tecnico',
            'alias' => $alias,
            'last_name' => 'Client',
        ]);

        $guest = Guest::query()->create([
            'project_id' => $project->id,
            'primary_first_name' => 'Marco',
            'primary_last_name' => 'Verdi',
            'email' => 'marco@example.com',
            'phone' => '+39 333 1234567',
            'rsvp_token' => $token,
        ]);

        $this->post('/event/' . $alias . '/rsvp', [
            'last_name' => 'verdi',
            'email' => 'marco@example.com',
            'phone' => '',
        ])->assertRedirect($guest->publicRsvpUrl());
    }

    public function test_project_rsvp_lookup_redirects_to_matching_guest_rsvp_by_normalized_phone(): void
    {
        $alias = 'test-tecnico-lookup-phone-' . str()->random(8);
        $token = str()->random(40);

        $project = Project::query()->create([
            'name' => 'Test tecnico',
            'alias' => $alias,
            'last_name' => 'Client',
        ]);

        $guest = Guest::query()->create([
            'project_id' => $project->id,
            'primary_first_name' => 'Marco',
            'primary_last_name' => 'Verdi',
            'phone' => '+39 333 1234567',
            'rsvp_token' => $token,
        ]);

        $this->post('/event/' . $alias . '/rsvp', [
            'last_name' => 'Verdi',
            'email' => '',
            'phone' => '39 333 123 4567',
        ])->assertRedirect($guest->publicRsvpUrl());
    }

    public function test_project_rsvp_lookup_returns_error_when_guest_is_not_found(): void
    {
        $alias = 'test-tecnico-lookup-miss-' . str()->random(8);

        $project = Project::query()->create([
            'name' => 'Test tecnico',
            'alias' => $alias,
            'last_name' => 'Client',
        ]);

        Guest::query()->create([
            'project_id' => $project->id,
            'primary_first_name' => 'Marco',
            'primary_last_name' => 'Verdi',
            'email' => 'marco@example.com',
        ]);

        $this->from('/event/' . $alias . '/rsvp')
            ->post('/event/' . $alias . '/rsvp', [
                'last_name' => 'Bianchi',
                'email' => 'unknown@example.com',
                'phone' => '',
            ])
            ->assertRedirect('/event/' . $alias . '/rsvp')
            ->assertSessionHas('lookup_error');
    }
}
