<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('templates')->updateOrInsert(
            [
                'slug' => 'mail-lead-questionnaire',
                'language' => 'en',
            ],
            [
                'title' => 'Lead questionnaire request',
                'subject' => 'Wedding questionnaire',
                'type' => 'html',
                'content' => <<<'HTML'
<p>Hi {{ client_names }},</p>

<p>
    To help me better understand your vision, style, and requirements - and to make sure we are
    the perfect match for your big day - I've prepared a quick questionnaire.
</p>

<p>
    It takes just 10 minutes to complete and will give me a clear picture of what you're looking
    for before we take the next steps.
</p>

<p>
    <a href="{{ questionnaire_link }}">Open the questionnaire</a>
</p>

<p>I look forward to reading your answers!</p>

<p>Warm regards,</p>
HTML,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );
    }

    public function down(): void
    {
        DB::table('templates')
            ->where('slug', 'mail-lead-questionnaire')
            ->where('language', 'en')
            ->delete();
    }
};
