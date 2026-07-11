<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('projects', 'time_format')) {
            return;
        }

        Schema::table('projects', function (Blueprint $table): void {
            $table->string('time_format', 3)->default('12h')->after('event_end_date');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('projects', 'time_format')) {
            return;
        }

        Schema::table('projects', function (Blueprint $table): void {
            $table->dropColumn('time_format');
        });
    }
};
