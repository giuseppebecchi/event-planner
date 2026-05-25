<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_seating_plans', function (Blueprint $table) {
            $table->string('background_image_path')->nullable()->after('notes');
        });
    }

    public function down(): void
    {
        Schema::table('project_seating_plans', function (Blueprint $table) {
            $table->dropColumn('background_image_path');
        });
    }
};
