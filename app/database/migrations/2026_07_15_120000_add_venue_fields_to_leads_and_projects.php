<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            if (! Schema::hasColumn('leads', 'venue_id')) {
                $table->foreignId('venue_id')
                    ->nullable()
                    ->after('location_request_type')
                    ->constrained('suppliers')
                    ->nullOnDelete();
            }
        });

        Schema::table('projects', function (Blueprint $table) {
            if (! Schema::hasColumn('projects', 'location_request_type')) {
                $table->string('location_request_type')->nullable()->after('locality');
            }

            if (! Schema::hasColumn('projects', 'venue_id')) {
                $table->foreignId('venue_id')
                    ->nullable()
                    ->after('location_request_type')
                    ->constrained('suppliers')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('projects', 'venue')) {
                $table->string('venue')->nullable()->after('venue_id');
            }

            if (! Schema::hasColumn('projects', 'ceremony_type')) {
                $table->string('ceremony_type')->nullable()->after('venue');
            }

            if (! Schema::hasColumn('projects', 'ceremony_details')) {
                $table->text('ceremony_details')->nullable()->after('ceremony_type');
            }

            if (! Schema::hasColumn('projects', 'ceremony_location')) {
                $table->string('ceremony_location')->nullable()->after('ceremony_details');
            }

            if (! Schema::hasColumn('projects', 'estimated_timings')) {
                $table->string('estimated_timings')->nullable()->after('ceremony_location');
            }

            if (! Schema::hasColumn('projects', 'additional_events')) {
                $table->text('additional_events')->nullable()->after('estimated_timings');
            }
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            if (Schema::hasColumn('projects', 'venue_id')) {
                $table->dropConstrainedForeignId('venue_id');
            }

            $columns = collect([
                'location_request_type',
                'venue',
                'ceremony_type',
                'ceremony_details',
                'ceremony_location',
                'estimated_timings',
                'additional_events',
            ])
                ->filter(fn (string $column): bool => Schema::hasColumn('projects', $column))
                ->all();

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });

        Schema::table('leads', function (Blueprint $table) {
            if (Schema::hasColumn('leads', 'venue_id')) {
                $table->dropConstrainedForeignId('venue_id');
            }
        });
    }
};
