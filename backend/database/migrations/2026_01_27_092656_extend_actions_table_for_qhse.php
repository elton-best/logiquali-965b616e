<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('actions', function (Blueprint $table) {
            // Workflow state
            $table->foreignId('workflow_state_id')->nullable()->after('status')->constrained('workflow_states')->nullOnDelete();
            
            // Plan d'actions
            $table->foreignId('plan_action_id')->nullable()->after('site_id')->constrained('plan_actions')->nullOnDelete();
            
            // Type étendu
            $table->dropColumn('type');
        });
        
        Schema::table('actions', function (Blueprint $table) {
            $table->enum('type', ['corrective', 'preventive', 'improvement', 'curative', 'emergency'])->default('corrective')->after('ref');
            
            // Suivi et avancement
            $table->integer('progress')->default(0)->after('deadline'); // 0-100%
            $table->json('progress_notes')->nullable()->after('progress'); // [{date, note, user_id}, ...]
            
            // Coûts et ressources
            $table->decimal('estimated_cost', 10, 2)->nullable()->after('progress_notes');
            $table->decimal('actual_cost', 10, 2)->nullable()->after('estimated_cost');
            $table->text('required_resources')->nullable()->after('actual_cost');
            
            // Priorité
            $table->enum('priority', ['low', 'medium', 'high', 'critical'])->default('medium')->after('type');
            
            // Validation et approbation
            $table->foreignId('approved_by')->nullable()->after('responsible_id')->constrained('users')->nullOnDelete();
            $table->date('approval_date')->nullable()->after('approved_by');
            $table->text('approval_notes')->nullable()->after('approval_date');
        });
    }

    public function down(): void
    {
        Schema::table('actions', function (Blueprint $table) {
            $table->dropForeign(['workflow_state_id']);
            $table->dropForeign(['plan_action_id']);
            $table->dropForeign(['approved_by']);
            $table->dropColumn([
                'workflow_state_id', 'plan_action_id', 'progress', 'progress_notes',
                'estimated_cost', 'actual_cost', 'required_resources', 'priority',
                'approved_by', 'approval_date', 'approval_notes'
            ]);
            $table->dropColumn('type');
        });
        
        Schema::table('actions', function (Blueprint $table) {
            $table->string('type')->nullable()->after('ref');
        });
    }
};
