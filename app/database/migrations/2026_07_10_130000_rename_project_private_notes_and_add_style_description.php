<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('projects', 'private_notes') && ! Schema::hasColumn('projects', 'internal_notes')) {
            Schema::table('projects', function (Blueprint $table): void {
                $table->renameColumn('private_notes', 'internal_notes');
            });
        } elseif (Schema::hasColumn('projects', 'private_notes') && Schema::hasColumn('projects', 'internal_notes')) {
            DB::table('projects')
                ->whereNull('internal_notes')
                ->whereNotNull('private_notes')
                ->update(['internal_notes' => DB::raw('private_notes')]);

            Schema::table('projects', function (Blueprint $table): void {
                $table->dropColumn('private_notes');
            });
        }

        Schema::table('projects', function (Blueprint $table): void {
            if (! Schema::hasColumn('projects', 'style_description')) {
                $table->text('style_description')->nullable()->after('wedding_period');
            }
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('projects', 'style_description')) {
            Schema::table('projects', function (Blueprint $table): void {
                $table->dropColumn('style_description');
            });
        }

        if (Schema::hasColumn('projects', 'internal_notes') && ! Schema::hasColumn('projects', 'private_notes')) {
            Schema::table('projects', function (Blueprint $table): void {
                $table->renameColumn('internal_notes', 'private_notes');
            });
        } elseif (Schema::hasColumn('projects', 'internal_notes') && Schema::hasColumn('projects', 'private_notes')) {
            DB::table('projects')
                ->whereNull('private_notes')
                ->whereNotNull('internal_notes')
                ->update(['private_notes' => DB::raw('internal_notes')]);

            Schema::table('projects', function (Blueprint $table): void {
                $table->dropColumn('internal_notes');
            });
        }
    }
};
