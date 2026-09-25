<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_types', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->unsignedInteger('order')->default(1);
            $table->timestamps();
            $table->softDeletes();
        });

        $now = now();
        $weddingId = DB::table('event_types')->insertGetId([
            'name' => 'Wedding',
            'slug' => 'wedding',
            'order' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('event_types')->insert([
            [
                'name' => 'Private event',
                'slug' => 'private-event',
                'order' => 2,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Elopement',
                'slug' => 'elopement',
                'order' => 3,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Proposal',
                'slug' => 'proposal',
                'order' => 4,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);

        Schema::table('leads', function (Blueprint $table) use ($weddingId): void {
            $table->foreignId('event_type_id')
                ->default($weddingId)
                ->after('source')
                ->constrained('event_types')
                ->restrictOnDelete();
        });

        Schema::table('projects', function (Blueprint $table) use ($weddingId): void {
            $table->foreignId('event_type_id')
                ->default($weddingId)
                ->after('lead_id')
                ->constrained('event_types')
                ->restrictOnDelete();
        });

        DB::table('leads')->update(['event_type_id' => $weddingId]);
        DB::table('projects')->update(['event_type_id' => $weddingId]);
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('event_type_id');
        });

        Schema::table('leads', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('event_type_id');
        });

        Schema::dropIfExists('event_types');
    }
};
