<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_seating_plans', function (Blueprint $table) {
            $table->decimal('viewport_zoom', 6, 2)->default(1)->after('background_image_path');
            $table->integer('viewport_x')->default(0)->after('viewport_zoom');
            $table->integer('viewport_y')->default(0)->after('viewport_x');
        });
    }

    public function down(): void
    {
        Schema::table('project_seating_plans', function (Blueprint $table) {
            $table->dropColumn([
                'viewport_zoom',
                'viewport_x',
                'viewport_y',
            ]);
        });
    }
};
