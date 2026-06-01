<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table): void {
            $table->string('alias')->nullable()->after('name');
        });

        $usedAliases = [];

        DB::table('projects')
            ->select(['id', 'name'])
            ->orderBy('id')
            ->get()
            ->each(function (object $project) use (&$usedAliases): void {
                $baseAlias = Str::slug((string) $project->name) ?: 'event';
                $alias = $baseAlias;
                $suffix = 2;

                while (in_array($alias, $usedAliases, true)) {
                    $alias = $baseAlias . '-' . $suffix;
                    $suffix++;
                }

                DB::table('projects')
                    ->where('id', $project->id)
                    ->update(['alias' => $alias]);

                $usedAliases[] = $alias;
            });

        Schema::table('projects', function (Blueprint $table): void {
            $table->unique('alias');
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table): void {
            $table->dropUnique(['alias']);
            $table->dropColumn('alias');
        });
    }
};
