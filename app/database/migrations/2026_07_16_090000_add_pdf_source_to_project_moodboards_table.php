<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_moodboards', function (Blueprint $table): void {
            if (! Schema::hasColumn('project_moodboards', 'pdf_file_path')) {
                $table->string('pdf_file_path')->nullable()->after('pinterest_board_url');
            }

            if (! Schema::hasColumn('project_moodboards', 'pdf_original_name')) {
                $table->string('pdf_original_name')->nullable()->after('pdf_file_path');
            }
        });
    }

    public function down(): void
    {
        Schema::table('project_moodboards', function (Blueprint $table): void {
            $columns = collect(['pdf_file_path', 'pdf_original_name'])
                ->filter(fn (string $column): bool => Schema::hasColumn('project_moodboards', $column))
                ->all();

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
