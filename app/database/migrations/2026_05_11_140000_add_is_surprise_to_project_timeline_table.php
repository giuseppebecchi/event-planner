<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_timeline', function (Blueprint $table) {
            $table->boolean('is_surprise')->default(false)->after('sunset_time');
        });
    }

    public function down(): void
    {
        Schema::table('project_timeline', function (Blueprint $table) {
            $table->dropColumn('is_surprise');
        });
    }
};
