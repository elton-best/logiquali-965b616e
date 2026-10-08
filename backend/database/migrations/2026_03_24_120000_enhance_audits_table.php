<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Ajouter les champs pour les compléments d'informations et rappels
        Schema::table('audits', function (Blueprint $table) {
            // Compléments d'informations
            $table->json('additional_info')->nullable()->after('attachments');
            $table->json('input_elements')->nullable()->after('additional_info'); // Éléments d'entrée (norme)
            $table->json('specific_points')->nullable()->after('input_elements'); // Points spécifiques
            
            // Rappels
            $table->boolean('reminder_enabled')->default(true)->after('specific_points');
            $table->integer('reminder_days_before')->default(30)->after('reminder_enabled'); // 1 mois par défaut
            $table->timestamp('reminder_sent_at')->nullable()->after('reminder_days_before');
            
            // Synthèse du système de management
            $table->json('sm_synthesis')->nullable()->after('reminder_sent_at');
            $table->timestamp('synthesis_generated_at')->nullable()->after('sm_synthesis');
        });

        // Table pour les rappels d'audit programmés
        Schema::create('audit_reminders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('audit_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Destinataire
            
            $table->enum('type', [
                'one_month',     // 1 mois avant
                'one_week',      // 1 semaine avant
                'one_day',       // 1 jour avant
                'overdue',       // En retard
                'custom'         // Personnalisé
            ])->default('one_month');
            
            $table->timestamp('scheduled_at');
            $table->timestamp('sent_at')->nullable();
            
            $table->enum('status', [
                'pending',
                'sent',
                'failed',
                'cancelled'
            ])->default('pending');
            
            $table->string('error_message')->nullable();
            
            $table->timestamps();
            
            // Index
            $table->index(['scheduled_at', 'status']);
            $table->index(['audit_id', 'type']);
        });

        // Table pour les éléments d'entrée normatifs
        Schema::create('audit_norm_inputs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('audit_id')->constrained()->onDelete('cascade');
            $table->foreignId('norm_section_id')->nullable()->constrained()->nullOnDelete();
            
            $table->string('requirement'); // Exigence normative
            $table->string('clause')->nullable(); // Clause ISO (ex: 9.2.2)
            $table->text('description')->nullable();
            $table->boolean('is_verified')->default(false);
            $table->text('verification_notes')->nullable();
            
            $table->integer('display_order')->default(0);
            
            $table->timestamps();
            
            $table->index(['audit_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_norm_inputs');
        Schema::dropIfExists('audit_reminders');
        
        Schema::table('audits', function (Blueprint $table) {
            $table->dropColumn([
                'additional_info',
                'input_elements',
                'specific_points',
                'reminder_enabled',
                'reminder_days_before',
                'reminder_sent_at',
                'sm_synthesis',
                'synthesis_generated_at',
            ]);
        });
    }
};
