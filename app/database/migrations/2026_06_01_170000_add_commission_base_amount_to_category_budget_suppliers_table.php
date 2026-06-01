<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('category_budget_suppliers', function (Blueprint $table): void {
            $table->decimal('commission_base_amount', 10, 2)->nullable()->after('commission_percentage');
        });
    }

    public function down(): void
    {
        Schema::table('category_budget_suppliers', function (Blueprint $table): void {
            $table->dropColumn('commission_base_amount');
        });
    }
};
