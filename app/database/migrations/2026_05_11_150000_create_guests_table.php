<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->unsignedInteger('rsvp_number')->nullable();
            $table->string('guest_list')->nullable();
            $table->string('group_name')->nullable();
            $table->string('primary_title')->nullable();
            $table->string('primary_first_name');
            $table->string('primary_last_name')->nullable();
            $table->string('primary_suffix')->nullable();
            $table->string('primary_role')->nullable();
            $table->string('primary_gender', 20)->nullable();
            $table->string('partner_title')->nullable();
            $table->string('partner_first_name')->nullable();
            $table->string('partner_last_name')->nullable();
            $table->string('partner_suffix')->nullable();
            $table->string('partner_role')->nullable();
            $table->string('partner_gender', 20)->nullable();
            $table->boolean('unspecified_plus_one')->default(false);
            $table->json('additional_guests')->nullable();
            $table->string('formal_addressing')->nullable();
            $table->string('address_line_1')->nullable();
            $table->string('address_line_2')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('country')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->boolean('invite_sent')->default(false);
            $table->boolean('ceremony')->default(true);
            $table->boolean('reception')->default(true);
            $table->boolean('out_of_town')->default(false);
            $table->boolean('gift_received')->default(false);
            $table->boolean('thank_you_sent')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['project_id', 'rsvp_number']);
            $table->index(['project_id', 'group_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guests');
    }
};
