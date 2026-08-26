<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        DB::table('templates')->updateOrInsert(
            [
                'slug' => 'supplier-courtesy-message',
                'language' => 'it',
            ],
            [
                'title' => 'Messaggio di cortesia fornitore non selezionato',
                'subject' => 'Feedback {{ wedding_reference }}',
                'type' => 'html',
                'content' => <<<'HTML'
<p>Gentile {{ supplier_name }},</p>

<p>
    Desideriamo informare che i nostri clienti hanno valutato attentamente la proposta,
    ma per questo specifico evento hanno deciso di optare per un'altra soluzione.
</p>

<p>
    Ringrazio per la disponibilità e per il tempo dedicatoci e speriamo di avere presto
    l'occasione di collaborare insieme per futuri eventi.
</p>

<p>Un cordiale saluto,</p>
HTML,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        );

        DB::table('templates')->updateOrInsert(
            [
                'slug' => 'supplier-courtesy-message',
                'language' => 'en',
            ],
            [
                'title' => 'Courtesy message for supplier not selected',
                'subject' => 'Feedback {{ wedding_reference }}',
                'type' => 'html',
                'content' => <<<'HTML'
<p>Dear {{ supplier_name }},</p>

<p>
    We would like to inform you that my clients have carefully reviewed your proposal,
    but for this specific event, they have decided to proceed with another solution.
</p>

<p>
    Thank you very much for your time and assistance. I hope to have the opportunity
    to collaborate with you on future events.
</p>

<p>Best regards,</p>
HTML,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        );
    }

    public function down(): void
    {
        DB::table('templates')
            ->where('slug', 'supplier-courtesy-message')
            ->whereIn('language', ['it', 'en'])
            ->delete();
    }
};
