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
        Schema::table('guests', function (Blueprint $table) {
            $table->string('rsvp_token', 64)->nullable()->after('project_id');
            $table->timestamp('rsvp_completed_at')->nullable()->after('rsvp_response');
        });

        DB::table('guests')
            ->whereNull('rsvp_token')
            ->orderBy('id')
            ->cursor()
            ->each(function (object $guest): void {
                DB::table('guests')
                    ->where('id', $guest->id)
                    ->update(['rsvp_token' => Str::random(40)]);
            });

        Schema::table('guests', function (Blueprint $table) {
            $table->unique('rsvp_token');
        });
    }

    public function down(): void
    {
        Schema::table('guests', function (Blueprint $table) {
            $table->dropUnique(['rsvp_token']);
            $table->dropColumn(['rsvp_token', 'rsvp_completed_at']);
        });
    }
};
