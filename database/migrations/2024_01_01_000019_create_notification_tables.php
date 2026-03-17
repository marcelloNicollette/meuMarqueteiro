<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Preferências de notificação e tokens de push (PWA).
 * Controla quais alertas cada prefeito recebe e por qual canal.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── Preferências de notificação ───────────────────────
        Schema::create('notification_preferences', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            // Briefing matinal
            $table->boolean('briefing_push')->default(true);
            $table->boolean('briefing_whatsapp')->default(false);
            $table->string('briefing_time', 5)->default('07:00')
                ->comment('Hora de entrega HH:MM');

            // Alertas de programas federais
            $table->boolean('federal_programs_push')->default(true);
            $table->boolean('federal_programs_email')->default(false);

            // Alertas de compromissos em risco
            $table->boolean('commitments_at_risk_push')->default(true);

            // Relatório mensal
            $table->boolean('monthly_report_push')->default(true);
            $table->boolean('monthly_report_whatsapp')->default(false);

            // Alertas fiscais
            $table->boolean('fiscal_alerts_push')->default(true);

            $table->timestamps();

            $table->unique('user_id');
        });

        // ── Tokens de push PWA ────────────────────────────────
        Schema::create('push_subscriptions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('endpoint')->unique();
            $table->string('public_key')->nullable();
            $table->string('auth_token')->nullable();

            $table->string('device_info')->nullable()
                ->comment('user-agent resumido');

            $table->timestamp('last_used_at')->nullable();
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->index(['user_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('push_subscriptions');
        Schema::dropIfExists('notification_preferences');
    }
};
