<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_timeline', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->nullOnDelete();
            $table->date('timeline_date');
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->time('sunset_time')->nullable();
            $table->string('location')->nullable();
            $table->string('title');
            $table->text('description')->nullable();
            $table->text('notes')->nullable();
            $table->json('image_paths')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_timeline');
    }
};
