<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_confirmation_emails', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('payment_id')->constrained()->cascadeOnDelete();
            $table->string('recipient');
            $table->json('payload');
            $table->unsignedInteger('attempts')->default(0);
            $table->timestamp('last_attempt_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
            $table->unique(['payment_id', 'recipient'], 'payment_confirmation_recipient_unique');
            $table->index(['sent_at', 'attempts']);
        });
        DB::table('templates')->insertOrIgnore([
            'slug' => 'mail-payment-received', 'language' => 'en', 'type' => 'html',
            'title' => 'Supplier payment received',
            'subject' => 'Payment received by {{ supplier_name }}',
            'content' => '<p>Dear {{ couple_names }},</p><p>We are pleased to confirm that {{ supplier_name }} has received your payment.</p><p><strong>Amount:</strong> {{ amount }}<br><strong>Payment:</strong> {{ reason }}<br><strong>Date received:</strong> {{ paid_date }}</p><p>You can review the details in <a href="{{ project_url }}">your project</a>.</p><p>Kind regards,</p>',
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_confirmation_emails');
        DB::table('templates')->where('slug', 'mail-payment-received')->where('language', 'en')->delete();
    }
};
