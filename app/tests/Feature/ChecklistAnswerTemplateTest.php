<?php

namespace Tests\Feature;

use App\Models\Checklist;
use App\Models\Project;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ChecklistAnswerTemplateTest extends TestCase
{
    use DatabaseTransactions;

    public function test_project_checklist_items_receive_default_answer_template(): void
    {
        $checklist = Checklist::query()->create([
            'title' => 'Menu checklist',
            'options' => [[
                'order' => 1,
                'title' => 'Define menu',
                'default' => true,
                'to_be_filled' => true,
                'insert_into_recap' => true,
                'answer_template' => '<p>Starter: ____</p><p>Main: ____</p><p>Dessert: ____</p>',
                'assigned_to' => 'client',
            ]],
        ]);

        $project = Project::query()->create([
            'name' => 'Wedding project',
            'last_name' => 'Client',
        ]);

        $project->syncChecklistOptionsFromTemplates();

        $this->assertDatabaseHas('project_checklist_options', [
            'project_id' => $project->id,
            'checkbox_id' => $checklist->id,
            'order' => 1,
            'to_be_filled' => true,
            'insert_into_recap' => true,
            'response' => '<p>Starter: ____</p><p>Main: ____</p><p>Dessert: ____</p>',
        ]);
    }

    public function test_checklist_options_clear_recap_and_template_when_not_fillable(): void
    {
        $data = \App\Filament\Resources\ChecklistResource::normalizeOptionsForSave([
            'options' => [[
                'order' => 1,
                'title' => 'Plain task',
                'to_be_filled' => false,
                'insert_into_recap' => true,
                'answer_template' => '<p>Should be removed</p>',
            ]],
        ]);

        $this->assertFalse($data['options'][0]['insert_into_recap']);
        $this->assertNull($data['options'][0]['answer_template']);
    }
}
