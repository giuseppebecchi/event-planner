<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->string('public_form_hash', 64)->nullable()->unique()->after('evaluation_outcome');
            $table->timestamp('form_sent_at')->nullable()->after('public_form_hash');
            $table->timestamp('form_completed_at')->nullable()->after('form_sent_at');
            $table->json('form_payload')->nullable()->after('form_completed_at');
        });

        DB::table('leads')
            ->whereNull('public_form_hash')
            ->orderBy('id')
            ->get(['id'])
            ->each(function (object $lead): void {
                DB::table('leads')
                    ->where('id', $lead->id)
                    ->update(['public_form_hash' => Str::lower(Str::random(32))]);
            });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropColumn([
                'public_form_hash',
                'form_sent_at',
                'form_completed_at',
                'form_payload',
            ]);
        });
    }
};
