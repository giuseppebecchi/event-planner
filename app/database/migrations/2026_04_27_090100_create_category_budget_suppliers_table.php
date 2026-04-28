<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('category_budget_suppliers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_budget_id')->constrained('category_budgets')->cascadeOnDelete();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->nullOnDelete();
            $table->dateTime('requested_at')->nullable();
            $table->longText('request_text')->nullable();
            $table->dateTime('responded_at')->nullable();
            $table->longText('response_text')->nullable();
            $table->string('availability_status')->default('pending');
            $table->json('proposed_dates')->nullable();
            $table->json('location_available_dates')->nullable();
            $table->longText('costs_and_conditions')->nullable();
            $table->longText('planner_notes')->nullable();
            $table->json('attachments')->nullable();
            $table->string('scouting_status')->default('contacted');
            $table->decimal('proposed_amount', 10, 2)->nullable();
            $table->longText('proposal_summary')->nullable();
            $table->string('proposal_status')->default('requested');
            $table->longText('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('category_budget_suppliers');
    }
};
