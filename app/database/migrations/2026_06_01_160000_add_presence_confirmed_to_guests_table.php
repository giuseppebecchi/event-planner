<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('guests', function (Blueprint $table): void {
            $table->boolean('presence_confirmed')->nullable()->after('rsvp_response');
        });

        DB::table('guests')
            ->whereNotNull('rsvp_completed_at')
            ->whereNull('presence_confirmed')
            ->update(['presence_confirmed' => true]);
    }

    public function down(): void
    {
        Schema::table('guests', function (Blueprint $table): void {
            $table->dropColumn('presence_confirmed');
        });
    }
};
