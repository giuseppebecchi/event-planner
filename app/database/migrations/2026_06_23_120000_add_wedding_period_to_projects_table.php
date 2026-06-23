<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('projects', 'wedding_period')) {
            Schema::table('projects', function (Blueprint $table): void {
                $table->string('wedding_period')->nullable()->after('locality');
            });
        }

        DB::table('projects')
            ->join('leads', 'leads.id', '=', 'projects.lead_id')
            ->whereNull('projects.wedding_period')
            ->whereNotNull('leads.wedding_period')
            ->update([
                'projects.wedding_period' => DB::raw('leads.wedding_period'),
            ]);
    }

    public function down(): void
    {
        if (Schema::hasColumn('projects', 'wedding_period')) {
            Schema::table('projects', function (Blueprint $table): void {
                $table->dropColumn('wedding_period');
            });
        }
    }
};
