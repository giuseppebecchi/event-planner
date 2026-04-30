<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_moodboards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->string('title');
            $table->string('board_type')->default('custom');
            $table->text('notes')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::table('project_images', function (Blueprint $table) {
            $table->foreignId('project_moodboard_id')
                ->nullable()
                ->after('project_id')
                ->constrained('project_moodboards')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('project_images', function (Blueprint $table) {
            $table->dropConstrainedForeignId('project_moodboard_id');
        });

        Schema::dropIfExists('project_moodboards');
    }
};
