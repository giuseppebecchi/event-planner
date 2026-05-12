<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('payment_modes')) {
            Schema::create('payment_modes', function (Blueprint $table) {
                $table->id();
                $table->string('name')->unique();
                $table->boolean('is_active')->default(true);
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();
            });
        }

        collect([
            'Bank transfer',
            'Credit Card',
            'Paypal',
            'Cash',
            'Other',
        ])->each(function (string $name, int $index): void {
            DB::table('payment_modes')->updateOrInsert(
                ['name' => $name],
                [
                    'is_active' => true,
                    'sort_order' => $index + 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        });

        Schema::table('suppliers', function (Blueprint $table) {
            if (! Schema::hasColumn('suppliers', 'accepted_payment_mode_ids')) {
                $table->text('accepted_payment_mode_ids')->nullable()->after('loc_payment_terms');
            }
        });

        Schema::table('payments', function (Blueprint $table) {
            if (! Schema::hasColumn('payments', 'payment_mode_id')) {
                $table->foreignId('payment_mode_id')
                    ->nullable()
                    ->after('category_budget_supplier_id')
                    ->constrained('payment_modes')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            if (Schema::hasColumn('payments', 'payment_mode_id')) {
                $table->dropConstrainedForeignId('payment_mode_id');
            }
        });

        Schema::table('suppliers', function (Blueprint $table) {
            if (Schema::hasColumn('suppliers', 'accepted_payment_mode_ids')) {
                $table->dropColumn('accepted_payment_mode_ids');
            }
        });

        Schema::dropIfExists('payment_modes');
    }
};
