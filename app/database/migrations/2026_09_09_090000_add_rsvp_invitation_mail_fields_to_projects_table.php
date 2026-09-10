<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table): void {
            $table->string('rsvp_invitation_email_subject')->nullable()->after('rsvp_submissions_locked');
            $table->longText('rsvp_invitation_email_html')->nullable()->after('rsvp_invitation_email_subject');
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table): void {
            $table->dropColumn([
                'rsvp_invitation_email_subject',
                'rsvp_invitation_email_html',
            ]);
        });
    }
};
