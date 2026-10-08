<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('non_conformities', function (Blueprint $table) {
            // Workflow state
            $table->foreignId('workflow_state_id')->nullable()->after('status')->constrained('workflow_states')->nullOnDelete();
            
            // Analyse des causes étendue (5 Pourquoi, Ishikawa)
            $table->json('cause_analysis')->nullable()->after('root_cause_analysis'); // {method: '5why|ishikawa', data: [...]}
            $table->json('impacts')->nullable()->after('cause_analysis'); // {quality: [...], safety: [...], environment: [...]}
            
            // Efficacité et vérification
            $table->date('resolution_date')->nullable()->after('deadline');
            $table->boolean('effectiveness_verified')->default(false)->after('resolution_date');
            $table->date('verification_date')->nullable()->after('effectiveness_verified');
            $table->foreignId('verified_by')->nullable()->after('verification_date')->constrained('users')->nullOnDelete();
            $table->text('verification_notes')->nullable()->after('verified_by');
            
            // Coûts et gravité étendue
            $table->decimal('cost_impact', 10, 2)->nullable()->after('verification_notes');
            $table->enum('priority', ['low', 'medium', 'high', 'critical'])->default('medium')->after('severity');
            
            // Source étendue
            $table->enum('source', ['audit', 'complaint', 'internal', 'external', 'risk', 'process'])->nullable()->after('type');
        });
    }

    public function down(): void
    {
        Schema::table('non_conformities', function (Blueprint $table) {
            $table->dropForeign(['workflow_state_id']);
            $table->dropForeign(['verified_by']);
            $table->dropColumn([
                'workflow_state_id', 'cause_analysis', 'impacts', 'resolution_date',
                'effectiveness_verified', 'verification_date', 'verified_by',
                'verification_notes', 'cost_impact', 'priority', 'source'
            ]);
        });
    }
};
