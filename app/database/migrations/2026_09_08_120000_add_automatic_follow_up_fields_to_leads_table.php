<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table): void {
            $table->boolean('send_automatic_follow_up')->default(false)->after('form_completed_at');
            $table->unsignedSmallInteger('follow_up_days')->default(7)->after('send_automatic_follow_up');
            $table->timestamp('follow_up_sent_at')->nullable()->after('follow_up_days');
        });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table): void {
            $table->dropColumn([
                'send_automatic_follow_up',
                'follow_up_days',
                'follow_up_sent_at',
            ]);
        });
    }
};
