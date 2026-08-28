<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            if (! Schema::hasColumn('leads', 'country')) {
                $table->string('country')->nullable()->after('city');
            }

            if (! Schema::hasColumn('leads', 'secondary_nationality')) {
                $table->string('secondary_nationality')->nullable()->after('secondary_phone');
            }
        });

        Schema::table('projects', function (Blueprint $table) {
            if (! Schema::hasColumn('projects', 'country')) {
                $table->string('country')->nullable()->after('city');
            }

            if (! Schema::hasColumn('projects', 'secondary_nationality')) {
                $table->string('secondary_nationality')->nullable()->after('secondary_phone');
            }
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $columns = collect(['country', 'secondary_nationality'])
                ->filter(fn (string $column): bool => Schema::hasColumn('projects', $column))
                ->all();

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });

        Schema::table('leads', function (Blueprint $table) {
            $columns = collect(['country', 'secondary_nationality'])
                ->filter(fn (string $column): bool => Schema::hasColumn('leads', $column))
                ->all();

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
