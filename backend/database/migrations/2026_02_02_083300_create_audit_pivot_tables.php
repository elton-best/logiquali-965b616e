<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tables pivots pour relations many-to-many audits
     */
    public function up(): void
    {
        // Audits <-> Processes (périmètre audit)
        Schema::create('audit_process', function (Blueprint $table) {
            $table->id();
            $table->foreignId('audit_id')->constrained()->cascadeOnDelete();
            $table->foreignId('process_id')->constrained()->cascadeOnDelete();
            $table->integer('priority')->default(1); // Priorité dans l'audit
            $table->enum('risk_level', ['critical', 'high', 'medium', 'low'])->nullable();
            $table->timestamps();
            
            $table->unique(['audit_id', 'process_id']);
        });
        
        // Audits <-> Auditors (équipe d'auditeurs)
        Schema::create('audit_auditor', function (Blueprint $table) {
            $table->id();
            $table->foreignId('audit_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('role', [
                'lead',           // Responsable d'audit
                'auditor',        // Auditeur
                'observer',       // Observateur
                'expert'          // Expert technique
            ])->default('auditor');
            $table->boolean('is_independent')->default(true); // Vérification indépendance
            $table->text('expertise_areas')->nullable(); // Domaines d'expertise
            $table->timestamps();
            
            $table->unique(['audit_id', 'user_id']);
        });
        
        // Audits <-> Auditees (personnes auditées)
        Schema::create('audit_auditee', function (Blueprint $table) {
            $table->id();
            $table->foreignId('audit_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('process_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('status', [
                'invited',        // Invité
                'confirmed',      // Confirmé
                'attended',       // Présent
                'absent'          // Absent
            ])->default('invited');
            $table->timestamp('invited_at')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamps();
            
            $table->unique(['audit_id', 'user_id', 'process_id']);
        });
        
        // Audits <-> Risks (liens avec risques identifiés)
        Schema::create('audit_risk', function (Blueprint $table) {
            $table->id();
            $table->foreignId('audit_id')->constrained()->cascadeOnDelete();
            $table->foreignId('risk_id')->constrained()->cascadeOnDelete();
            $table->enum('verification_status', [
                'to_verify',      // À vérifier
                'verified_ok',    // Maîtrisé
                'verified_nok',   // Non maîtrisé
                'not_applicable'  // N/A
            ])->default('to_verify');
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->unique(['audit_id', 'risk_id']);
        });
        
        // Ajout de colonnes quarter et frequency pour planification périodique
        Schema::table('audits', function (Blueprint $table) {
            $table->integer('quarter')->nullable()->after('planned_date'); // Q1, Q2, Q3, Q4
            $table->enum('frequency', ['annual', 'biannual', 'quarterly', 'monthly', 'ad_hoc'])->nullable()->after('quarter');
        });
    }

    public function down(): void
    {
        Schema::table('audits', function (Blueprint $table) {
            $table->dropColumn(['quarter', 'frequency']);
        });
        
        Schema::dropIfExists('audit_risk');
        Schema::dropIfExists('audit_auditee');
        Schema::dropIfExists('audit_auditor');
        Schema::dropIfExists('audit_process');
    }
};
