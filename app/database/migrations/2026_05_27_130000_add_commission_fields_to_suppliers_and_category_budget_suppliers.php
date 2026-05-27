<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('suppliers', function (Blueprint $table): void {
            $table->boolean('default_commission_enabled')->default(false)->after('price_range');
            $table->decimal('default_commission_percentage', 5, 2)->nullable()->after('default_commission_enabled');
        });

        Schema::table('category_budget_suppliers', function (Blueprint $table): void {
            $table->string('commission_mode')->default('NONE')->after('proposed_amount');
            $table->decimal('commission_percentage', 5, 2)->nullable()->after('commission_mode');
            $table->decimal('commission_amount', 10, 2)->default(0)->after('commission_percentage');
            $table->decimal('commission_total_amount_payed', 10, 2)->default(0)->after('commission_amount');
            $table->json('commission_payments_json')->nullable()->after('commission_total_amount_payed');
        });
    }

    public function down(): void
    {
        Schema::table('category_budget_suppliers', function (Blueprint $table): void {
            $table->dropColumn([
                'commission_mode',
                'commission_percentage',
                'commission_amount',
                'commission_total_amount_payed',
                'commission_payments_json',
            ]);
        });

        Schema::table('suppliers', function (Blueprint $table): void {
            $table->dropColumn([
                'default_commission_enabled',
                'default_commission_percentage',
            ]);
        });
    }
};
