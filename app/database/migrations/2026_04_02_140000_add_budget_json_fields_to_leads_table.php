<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->json('budget_vendors')->nullable()->after('form_payload');
            $table->json('budget_wedding_planner')->nullable()->after('budget_vendors');
            $table->json('budget_wedding_planner_extra_services')->nullable()->after('budget_wedding_planner');
            $table->json('budget_wedding_planner_special_packages')->nullable()->after('budget_wedding_planner_extra_services');
        });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropColumn([
                'budget_vendors',
                'budget_wedding_planner',
                'budget_wedding_planner_extra_services',
                'budget_wedding_planner_special_packages',
            ]);
        });
    }
};
