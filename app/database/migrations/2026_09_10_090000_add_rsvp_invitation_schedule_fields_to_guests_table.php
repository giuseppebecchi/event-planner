<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('guests', function (Blueprint $table) {
            $table->timestamp('rsvp_invitation_scheduled_at')->nullable()->after('invite_sent');
            $table->timestamp('rsvp_invitation_sent_at')->nullable()->after('rsvp_invitation_scheduled_at');

            $table->index(['rsvp_invitation_scheduled_at', 'rsvp_invitation_sent_at'], 'guests_rsvp_invitation_schedule_index');
        });
    }

    public function down(): void
    {
        Schema::table('guests', function (Blueprint $table) {
            $table->dropIndex('guests_rsvp_invitation_schedule_index');
            $table->dropColumn(['rsvp_invitation_scheduled_at', 'rsvp_invitation_sent_at']);
        });
    }
};
