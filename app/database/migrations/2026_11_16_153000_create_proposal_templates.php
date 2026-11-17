<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('proposal_templates', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->boolean('is_active')->default(true);
            $table->json('configuration');
            $table->timestamps();
        });
        Schema::table('leads', function (Blueprint $table): void {
            $table->foreignId('proposal_template_id')->nullable()->constrained()->restrictOnDelete();
            $table->json('proposal_layout')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('proposal_template_id');
            $table->dropColumn('proposal_layout');
        });
        Schema::dropIfExists('proposal_templates');
    }
};
