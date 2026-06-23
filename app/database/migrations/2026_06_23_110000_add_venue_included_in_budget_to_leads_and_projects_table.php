<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->boolean('venue_included_in_budget')->default(false)->after('budget_amount');
        });

        Schema::table('projects', function (Blueprint $table) {
            $table->boolean('venue_included_in_budget')->default(false)->after('budget_amount');
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn('venue_included_in_budget');
        });

        Schema::table('leads', function (Blueprint $table) {
            $table->dropColumn('venue_included_in_budget');
        });
    }
};
