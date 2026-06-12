<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table): void {
            $table->date('event_date')->nullable()->after('locality');
        });

        DB::table('projects')
            ->whereNull('event_date')
            ->whereNotNull('event_start_date')
            ->update(['event_date' => DB::raw('event_start_date')]);
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table): void {
            $table->dropColumn('event_date');
        });
    }
};
