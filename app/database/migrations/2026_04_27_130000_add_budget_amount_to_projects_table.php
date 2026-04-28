<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->decimal('budget_amount', 10, 2)->nullable()->after('final_guest_count');
        });

        DB::table('projects')
            ->join('leads', 'leads.id', '=', 'projects.lead_id')
            ->whereNull('projects.budget_amount')
            ->whereNotNull('leads.budget_amount')
            ->update([
                'projects.budget_amount' => DB::raw('leads.budget_amount'),
            ]);
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn('budget_amount');
        });
    }
};
