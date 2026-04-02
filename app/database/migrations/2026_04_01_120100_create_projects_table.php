<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->nullable()->constrained('leads')->nullOnDelete();
            $table->string('name');
            $table->string('partner_one_name');
            $table->string('partner_two_name')->nullable();
            $table->string('reference_email')->nullable();
            $table->string('primary_phone')->nullable();
            $table->string('secondary_phone')->nullable();
            $table->string('nationality')->nullable();
            $table->string('preferred_language')->nullable();
            $table->text('address')->nullable();
            $table->longText('private_notes')->nullable();
            $table->json('documents')->nullable();
            $table->string('region')->nullable();
            $table->string('locality')->nullable();
            $table->date('event_start_date')->nullable();
            $table->date('event_end_date')->nullable();
            $table->unsignedInteger('estimated_guest_count')->nullable();
            $table->unsignedInteger('final_guest_count')->nullable();
            $table->string('status')->default('proposal');
            $table->longText('logistics_notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
