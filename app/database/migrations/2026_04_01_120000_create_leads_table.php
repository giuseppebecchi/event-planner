<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->date('requested_at')->nullable();
            $table->string('source')->nullable();
            $table->string('couple_name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('nationality')->nullable();
            $table->unsignedInteger('estimated_guest_count')->nullable();
            $table->string('wedding_period')->nullable();
            $table->string('desired_region')->nullable();
            $table->string('ceremony_type')->nullable();
            $table->text('ceremony_details')->nullable();
            $table->string('location_request_type')->nullable();
            $table->text('additional_events')->nullable();
            $table->decimal('budget_amount', 12, 2)->nullable();
            $table->text('style_description')->nullable();
            $table->string('status')->default('new');
            $table->string('evaluation_outcome')->default('maybe');
            $table->longText('internal_notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};
