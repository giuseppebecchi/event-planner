<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('category_budget_suppliers', function (Blueprint $table): void {
            $table->json('event_day_allocations')->nullable()->after('proposed_amount');
        });
    }

    public function down(): void
    {
        Schema::table('category_budget_suppliers', function (Blueprint $table): void {
            $table->dropColumn('event_day_allocations');
        });
    }
};
