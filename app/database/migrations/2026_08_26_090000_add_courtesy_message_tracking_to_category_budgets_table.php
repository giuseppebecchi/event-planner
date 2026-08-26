<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('category_budgets', function (Blueprint $table): void {
            $table->boolean('ref_courtesy_messagge_sent_at')->default(false)->after('budget_status');
        });
    }

    public function down(): void
    {
        Schema::table('category_budgets', function (Blueprint $table): void {
            $table->dropColumn('ref_courtesy_messagge_sent_at');
        });
    }
};
