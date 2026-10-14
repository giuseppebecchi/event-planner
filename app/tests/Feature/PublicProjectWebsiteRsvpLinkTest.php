<?php

namespace Tests\Feature;

use App\Filament\Resources\ProjectResource\Pages\ManageProjectWebsite;
use App\Models\Guest;
use App\Models\Project;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class PublicProjectWebsiteRsvpLinkTest extends TestCase
{
    use DatabaseTransactions;

    public function test_website_editor_provides_guidance_for_every_section(): void
    {
        $page = new ManageProjectWebsite;

        $this->assertSame(array_keys($page->tabs()), array_keys($page->sectionGuidance()));
        $this->assertStringContainsString('dress code', $page->sectionGuidance()['attire']);
    }

    public function test_legacy_website_configuration_keeps_attire_disabled_without_changing_saved_content(): void
    {
        $project = Project::query()->create([
            'name' => 'Legacy website',
            'alias' => 'legacy-website-'.str()->random(8),
            'last_name' => 'Client',
            'website_json' => [
                'gallery' => [
                    'enabled' => true,
                    'title' => 'Our gallery',
                    'intro' => 'Existing gallery copy',
                    'images' => [],
                ],
                'events' => [
                    'enabled' => true,
                    'title' => 'Welcome Party & Wedding Event',
                    'intro' => 'Existing events copy',
                    'items' => [],
                ],
            ],
        ]);

        $website = $project->websiteConfiguration();

        $this->assertFalse($website['attire']['enabled']);
        $this->assertSame('Our gallery', $website['gallery']['title']);
        $this->assertSame('Welcome Party & Wedding Event', $website['events']['title']);

        $this->get('/event/'.$project->alias)
            ->assertOk()
            ->assertSee('Existing gallery copy')
            ->assertSee('Existing events copy')
            ->assertDontSee('id="attire"', false);
    }

    public function test_enabled_attire_section_displays_title_description_and_images_after_gallery(): void
    {
        $project = Project::query()->create([
            'name' => 'Website with attire',
            'alias' => 'website-attire-'.str()->random(8),
            'last_name' => 'Client',
            'website_json' => [
                'gallery' => [
                    'enabled' => true,
                    'title' => 'Gallery',
                    'intro' => '',
                    'images' => [],
                ],
                'attire' => [
                    'enabled' => true,
                    'title' => 'Dress code',
                    'intro' => 'Garden formal attire is suggested.',
                    'images' => [
                        ['url' => 'https://example.com/attire.jpg', 'caption' => 'Attire inspiration'],
                    ],
                ],
            ],
        ]);

        $response = $this->get('/event/'.$project->alias)
            ->assertOk()
            ->assertSee('Dress code')
            ->assertSee('Garden formal attire is suggested.')
            ->assertSee('https://example.com/attire.jpg', false)
            ->assertSee('Attire inspiration');

        $this->assertLessThan(
            strpos($response->getContent(), 'id="attire"'),
            strpos($response->getContent(), 'id="gallery"'),
        );
    }

    public function test_event_website_without_rsvp_token_keeps_rsvp_section_without_personal_link(): void
    {
        $alias = 'test-tecnico-no-rsvp-'.str()->random(8);
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

        $this->get('/event/'.$alias)
            ->assertOk()
            ->assertSee('RSVP')
            ->assertSee('Find your RSVP')
            ->assertSee(route('public.project-rsvp.lookup', ['projectAlias' => $project->alias]))
            ->assertDontSee('Open RSVP form');
    }

    public function test_event_website_with_rsvp_token_shows_link_to_guest_rsvp_form(): void
    {
        $alias = 'test-tecnico-rsvp-'.str()->random(8);
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

        $this->get('/event/'.$alias.'/rsvp/'.$token)
            ->assertOk()
            ->assertSee('Dear Guest please use the following personal RSVP link.')
            ->assertDontSee('Please use the personal RSVP link you received with your invitation.')
            ->assertSee('Open RSVP form')
            ->assertSee($guest->publicRsvpUrl());
    }

    public function test_project_rsvp_lookup_redirects_to_matching_guest_rsvp_by_email(): void
    {
        $alias = 'test-tecnico-lookup-email-'.str()->random(8);
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

        $this->post('/event/'.$alias.'/rsvp', [
            'last_name' => 'verdi',
            'email' => 'marco@example.com',
            'phone' => '',
        ])->assertRedirect($guest->publicRsvpUrl());
    }

    public function test_project_rsvp_lookup_redirects_to_matching_guest_rsvp_by_normalized_phone(): void
    {
        $alias = 'test-tecnico-lookup-phone-'.str()->random(8);
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

        $this->post('/event/'.$alias.'/rsvp', [
            'last_name' => 'Verdi',
            'email' => '',
            'phone' => '39 333 123 4567',
        ])->assertRedirect($guest->publicRsvpUrl());
    }

    public function test_project_rsvp_lookup_returns_error_when_guest_is_not_found(): void
    {
        $alias = 'test-tecnico-lookup-miss-'.str()->random(8);

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

        $this->from('/event/'.$alias.'/rsvp')
            ->post('/event/'.$alias.'/rsvp', [
                'last_name' => 'Bianchi',
                'email' => 'unknown@example.com',
                'phone' => '',
            ])
            ->assertRedirect('/event/'.$alias.'/rsvp')
            ->assertSessionHas('lookup_error');
    }
}
