<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('actions', function (Blueprint $table) {
            // Ajouter source polymorphique
            if (!Schema::hasColumn('actions', 'source_type')) {
                $table->string('source_type', 50)->nullable()->after('process_id');
            }
            if (!Schema::hasColumn('actions', 'source_id')) {
                $table->bigInteger('source_id')->nullable()->after('source_type');
            }
            
            // Ajouter relations spécifiques (vérifier existence des tables)
            if (!Schema::hasColumn('actions', 'objective_id') && Schema::hasTable('objectives')) {
                $table->foreignId('objective_id')->nullable()->after('source_id')->constrained()->nullOnDelete();
            }
            if (!Schema::hasColumn('actions', 'risk_id') && Schema::hasTable('risks')) {
                $table->foreignId('risk_id')->nullable()->after('objective_id')->constrained('risks')->nullOnDelete();
            }
            if (!Schema::hasColumn('actions', 'non_conformity_id') && Schema::hasTable('non_conformities')) {
                $table->foreignId('non_conformity_id')->nullable()->after('risk_id')->constrained()->nullOnDelete();
            }
            if (!Schema::hasColumn('actions', 'audit_id') && Schema::hasTable('audits')) {
                $table->foreignId('audit_id')->nullable()->after('non_conformity_id')->constrained()->nullOnDelete();
            }
            // Ignorer complaint_id si table complaints n'existe pas
            if (!Schema::hasColumn('actions', 'complaint_id') && Schema::hasTable('reclamations')) {
                $table->foreignId('complaint_id')->nullable()->after('audit_id')->constrained('reclamations')->nullOnDelete();
            }
            
            // Ajouter priorité
            if (!Schema::hasColumn('actions', 'priority')) {
                $table->enum('priority', ['low', 'medium', 'high', 'critical'])->default('medium')->after('type');
            }
            
            // Ajouter résultat attendu
            if (!Schema::hasColumn('actions', 'expected_result')) {
                $table->text('expected_result')->nullable()->after('description');
            }
            
            // Ajouter dates
            if (!Schema::hasColumn('actions', 'start_date')) {
                $table->date('start_date')->nullable()->after('expected_result');
            }
            if (!Schema::hasColumn('actions', 'completion_date')) {
                $table->date('completion_date')->nullable()->after('deadline');
            }
            
            // Ajouter progression
            if (!Schema::hasColumn('actions', 'progress_percentage')) {
                $table->integer('progress_percentage')->default(0)->after('status');
            }
            
            // Enrichir efficacité
            if (!Schema::hasColumn('actions', 'effectiveness_verification_date')) {
                $table->date('effectiveness_verification_date')->nullable()->after('effectiveness_verified');
            }
            if (!Schema::hasColumn('actions', 'effectiveness_comments')) {
                $table->text('effectiveness_comments')->nullable()->after('effectiveness_verification_date');
            }
            if (!Schema::hasColumn('actions', 'verified_by')) {
                $table->foreignId('verified_by')->nullable()->after('effectiveness_comments')->constrained('users')->nullOnDelete();
            }
            
            // Ajouter coûts
            if (!Schema::hasColumn('actions', 'estimated_cost')) {
                $table->decimal('estimated_cost', 10, 2)->nullable()->after('verified_by');
            }
            if (!Schema::hasColumn('actions', 'actual_cost')) {
                $table->decimal('actual_cost', 10, 2)->nullable()->after('estimated_cost');
            }
        });
    }

    public function down(): void
    {
        Schema::table('actions', function (Blueprint $table) {
            $table->dropForeign(['objective_id', 'risk_id', 'non_conformity_id', 'audit_id', 'complaint_id', 'verified_by']);
            $table->dropColumn([
                'source_type', 'source_id', 'objective_id', 'risk_id', 'non_conformity_id', 
                'audit_id', 'complaint_id', 'priority', 'expected_result', 'start_date', 
                'completion_date', 'progress_percentage', 'effectiveness_verification_date',
                'effectiveness_comments', 'verified_by', 'estimated_cost', 'actual_cost'
            ]);
        });
    }
};
