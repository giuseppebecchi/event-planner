<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_tables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_seating_plan_id')->constrained('project_seating_plans')->cascadeOnDelete();
            $table->string('name')->nullable();
            $table->decimal('center_x', 10, 2)->default(0);
            $table->decimal('center_y', 10, 2)->default(0);
            $table->decimal('rotation', 8, 2)->default(0);
            $table->string('table_type')->default('round');
            $table->decimal('primary_dimension', 10, 2)->default(0);
            $table->decimal('secondary_dimension', 10, 2)->nullable();
            $table->unsignedInteger('seats_total')->nullable();
            $table->json('seats_by_side_json')->nullable();
            $table->json('guest_assignments_json')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['project_seating_plan_id', 'sort_order']);
            $table->index('table_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_tables');
    }
};
