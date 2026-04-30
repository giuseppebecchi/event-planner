<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_checklist_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->nullOnDelete();
            $table->foreignId('category_budget_id')->nullable()->constrained('category_budgets')->nullOnDelete();
            $table->foreignId('checkbox_id')->constrained('checklists')->cascadeOnDelete();
            $table->unsignedInteger('order');
            $table->text('title');
            $table->boolean('default')->default(false);
            $table->string('anticipation')->nullable();
            $table->string('assigned_to')->default('none');
            $table->date('due_date')->nullable();
            $table->boolean('enabled')->default(false);
            $table->timestamps();

            $table->unique(['project_id', 'checkbox_id', 'order'], 'project_checklist_option_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_checklist_options');
    }
};
