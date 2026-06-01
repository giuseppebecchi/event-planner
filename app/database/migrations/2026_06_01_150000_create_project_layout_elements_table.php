<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_layout_elements', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_seating_plan_id')->constrained('project_seating_plans')->cascadeOnDelete();
            $table->string('element_type')->default('space');
            $table->string('shape')->nullable();
            $table->string('label')->nullable();
            $table->decimal('center_x', 10, 2)->default(0);
            $table->decimal('center_y', 10, 2)->default(0);
            $table->decimal('rotation', 8, 2)->default(0);
            $table->decimal('width', 10, 2)->default(120);
            $table->decimal('height', 10, 2)->default(80);
            $table->string('background_color', 20)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['project_seating_plan_id', 'sort_order']);
            $table->index('element_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_layout_elements');
    }
};
