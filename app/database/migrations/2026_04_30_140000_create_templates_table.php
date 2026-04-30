<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('templates', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->string('slug');
            $table->string('language', 10)->default('en');
            $table->string('type', 20)->default('html');
            $table->longText('content')->nullable();
            $table->timestamps();

            $table->unique(['slug', 'language']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('templates');
    }
};
