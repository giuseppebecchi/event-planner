<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('strategic_infos', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('category_id')->constrained('categories')->cascadeOnDelete();
            $table->string('title');
            $table->unsignedInteger('order')->default(1);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('project_strategic_infos', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->foreignId('strategic_info_id')->nullable()->constrained('strategic_infos')->nullOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->string('title');
            $table->longText('content')->nullable();
            $table->json('image_paths')->nullable();
            $table->string('status')->default('draft');
            $table->dateTime('completed_at')->nullable();
            $table->timestamps();

            $table->unique(['project_id', 'strategic_info_id'], 'project_strategic_info_unique');
            $table->index(['project_id', 'category_id', 'status'], 'project_strategic_info_lookup');
        });

        $now = now();
        $definitions = [
            ['title' => 'Mise en place', 'category' => 'Catering', 'order' => 1],
            ['title' => 'Menu', 'category' => 'Catering', 'order' => 2],
            ['title' => 'Playlist', 'category' => 'Music / DJ', 'order' => 1],
        ];

        foreach ($definitions as $definition) {
            $categoryId = DB::table('categories')
                ->whereNull('deleted_at')
                ->where('label', $definition['category'])
                ->value('id');

            if (! $categoryId) {
                continue;
            }

            $strategicInfoId = DB::table('strategic_infos')->insertGetId([
                'category_id' => $categoryId,
                'title' => $definition['title'],
                'order' => $definition['order'],
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $rows = DB::table('projects')
                ->whereNull('deleted_at')
                ->pluck('id')
                ->map(fn (int $projectId): array => [
                    'project_id' => $projectId,
                    'strategic_info_id' => $strategicInfoId,
                    'category_id' => $categoryId,
                    'title' => $definition['title'],
                    'content' => null,
                    'image_paths' => null,
                    'status' => 'draft',
                    'completed_at' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ])
                ->all();

            if ($rows !== []) {
                DB::table('project_strategic_infos')->insert($rows);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('project_strategic_infos');
        Schema::dropIfExists('strategic_infos');
    }
};
