<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('category_budgets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->decimal('initial_estimated_amount', 10, 2)->nullable();
            $table->decimal('comparison_amount', 10, 2)->nullable();
            $table->decimal('final_amount', 10, 2)->nullable();
            $table->string('budget_status')->default('hypothetical');
            $table->longText('notes')->nullable();
            $table->timestamps();

            $table->unique(['project_id', 'category_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('category_budgets');
    }
};
