<?php

namespace Tests\Feature;

use App\Filament\Resources\LeadResource\Pages\ViewLeadFormData;
use App\Models\Lead;
use App\Models\Role;
use App\Models\User;
use App\Support\LeadQuestionnaire;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class LeadQuestionnaireImageUploadTest extends TestCase
{
    use DatabaseTransactions;

    public function test_question_29_accepts_up_to_five_visual_inspiration_images(): void
    {
        Storage::fake('public');
        Notification::fake();

        $lead = Lead::query()->create([
            'couple_name' => 'Visual Couple',
            'email' => 'visual@example.com',
        ]);

        $this->get(route('public.lead-form.show', $lead->public_form_hash))
            ->assertOk()
            ->assertSee('Do you already have any visual inspiration for your wedding?')
            ->assertSeeHtml('enctype="multipart/form-data"')
            ->assertSeeHtml('name="visual_inspirations[]"')
            ->assertSee('29');

        $payload = $this->validPayload();
        $payload['visual_inspirations'] = [
            UploadedFile::fake()->image('inspiration-one.jpg', 2400, 1200),
            UploadedFile::fake()->image('inspiration-two.png', 800, 1200),
        ];

        $this->post(route('public.lead-form.submit', $lead->public_form_hash), $payload)
            ->assertRedirect(route('public.lead-form.show', $lead->public_form_hash));

        $paths = $lead->refresh()->form_payload['visual_inspirations'];

        $this->assertCount(2, $paths);

        foreach ($paths as $path) {
            Storage::disk('public')->assertExists($path);
            $size = getimagesize(Storage::disk('public')->path($path));

            $this->assertNotFalse($size);
            $this->assertLessThanOrEqual(1600, max($size[0], $size[1]));
        }

        $adminRole = Role::query()->firstOrCreate(['name' => Role::ADMIN]);
        $this->actingAs(User::factory()->create(['role_id' => $adminRole->id]));

        Livewire::test(ViewLeadFormData::class, ['record' => $lead->id])
            ->assertSee('Visual inspiration')
            ->assertSeeHtml('lead-form-data-image-grid')
            ->assertSeeHtml(Storage::disk('public')->url($paths[0]));
    }

    public function test_question_29_rejects_more_than_five_images(): void
    {
        Storage::fake('public');
        Notification::fake();

        $lead = Lead::query()->create([
            'couple_name' => 'Visual Couple',
            'email' => 'visual@example.com',
        ]);
        $payload = $this->validPayload();
        $payload['visual_inspirations'] = collect(range(1, 6))
            ->map(fn (int $index): UploadedFile => UploadedFile::fake()->image("inspiration-{$index}.jpg", 100, 100))
            ->all();

        $this->post(route('public.lead-form.submit', $lead->public_form_hash), $payload)
            ->assertSessionHasErrors('visual_inspirations');

        $this->assertNull($lead->refresh()->form_completed_at);
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    protected function validPayload(): array
    {
        return collect(LeadQuestionnaire::definition())
            ->mapWithKeys(function (array $question): array {
                $key = $question['key'];
                $type = $question['type'] ?? 'text';

                if ($type === 'checkboxes') {
                    return [$key => [($question['options'] ?? ['Option'])[0]]];
                }

                if (in_array($type, ['radio', 'select'], true)) {
                    return [$key => ($question['options'] ?? ['Option'])[0]];
                }

                return [$key => ($question['required'] ?? false) ? 'Example answer' : null];
            })
            ->all();
    }
}
