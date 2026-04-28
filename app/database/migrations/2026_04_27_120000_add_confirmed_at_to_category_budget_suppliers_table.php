<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('category_budget_suppliers', function (Blueprint $table) {
            $table->dateTime('confirmed_at')->nullable()->after('proposal_status');
        });
    }

    public function down(): void
    {
        Schema::table('category_budget_suppliers', function (Blueprint $table) {
            $table->dropColumn('confirmed_at');
        });
    }
};
