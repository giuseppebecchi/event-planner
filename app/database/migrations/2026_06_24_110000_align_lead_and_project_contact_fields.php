<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            if (! Schema::hasColumn('leads', 'secondary_first_name')) {
                $table->string('secondary_first_name')->nullable()->after('nationality');
            }

            if (! Schema::hasColumn('leads', 'secondary_last_name')) {
                $table->string('secondary_last_name')->nullable()->after('secondary_first_name');
            }

            if (! Schema::hasColumn('leads', 'secondary_email')) {
                $table->string('secondary_email')->nullable()->after('secondary_last_name');
            }

            if (! Schema::hasColumn('leads', 'secondary_phone')) {
                $table->string('secondary_phone')->nullable()->after('secondary_email');
            }
        });

        Schema::table('projects', function (Blueprint $table) {
            if (Schema::hasColumn('projects', 'partner_one_name') && ! Schema::hasColumn('projects', 'last_name')) {
                $table->renameColumn('partner_one_name', 'last_name');
            }

            if (Schema::hasColumn('projects', 'partner_two_name') && ! Schema::hasColumn('projects', 'secondary_last_name')) {
                $table->renameColumn('partner_two_name', 'secondary_last_name');
            }

            if (Schema::hasColumn('projects', 'reference_email') && ! Schema::hasColumn('projects', 'email')) {
                $table->renameColumn('reference_email', 'email');
            }

            if (Schema::hasColumn('projects', 'partner_2_reference_email') && ! Schema::hasColumn('projects', 'secondary_email')) {
                $table->renameColumn('partner_2_reference_email', 'secondary_email');
            }

            if (Schema::hasColumn('projects', 'primary_phone') && ! Schema::hasColumn('projects', 'phone')) {
                $table->renameColumn('primary_phone', 'phone');
            }
        });

        Schema::table('projects', function (Blueprint $table) {
            if (! Schema::hasColumn('projects', 'first_name')) {
                $table->string('first_name')->nullable()->after('name');
            }

            if (! Schema::hasColumn('projects', 'secondary_first_name')) {
                $table->string('secondary_first_name')->nullable()->after('secondary_last_name');
            }
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            if (Schema::hasColumn('projects', 'first_name')) {
                $table->dropColumn('first_name');
            }

            if (Schema::hasColumn('projects', 'secondary_first_name')) {
                $table->dropColumn('secondary_first_name');
            }
        });

        Schema::table('projects', function (Blueprint $table) {
            if (Schema::hasColumn('projects', 'last_name') && ! Schema::hasColumn('projects', 'partner_one_name')) {
                $table->renameColumn('last_name', 'partner_one_name');
            }

            if (Schema::hasColumn('projects', 'secondary_last_name') && ! Schema::hasColumn('projects', 'partner_two_name')) {
                $table->renameColumn('secondary_last_name', 'partner_two_name');
            }

            if (Schema::hasColumn('projects', 'email') && ! Schema::hasColumn('projects', 'reference_email')) {
                $table->renameColumn('email', 'reference_email');
            }

            if (Schema::hasColumn('projects', 'secondary_email') && ! Schema::hasColumn('projects', 'partner_2_reference_email')) {
                $table->renameColumn('secondary_email', 'partner_2_reference_email');
            }

            if (Schema::hasColumn('projects', 'phone') && ! Schema::hasColumn('projects', 'primary_phone')) {
                $table->renameColumn('phone', 'primary_phone');
            }
        });

        Schema::table('leads', function (Blueprint $table) {
            $columns = collect(['secondary_first_name', 'secondary_last_name', 'secondary_email', 'secondary_phone'])
                ->filter(fn (string $column): bool => Schema::hasColumn('leads', $column))
                ->all();

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
