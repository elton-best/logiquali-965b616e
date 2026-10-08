<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('objectives', function (Blueprint $table) {
            // Workflow state
            $table->foreignId('workflow_state_id')->nullable()->after('status')->constrained('workflow_states')->nullOnDelete();
            
            // Lien avec indicateur KPI
            $table->foreignId('indicateur_id')->nullable()->after('strategic_axis_id')->constrained('indicateurs')->nullOnDelete();
            
            // Avancement et mesure
            $table->integer('progress')->default(0)->after('current_value'); // 0-100%
            $table->date('start_date')->nullable()->after('description');
            $table->json('milestones')->nullable()->after('progress'); // [{date, description, status}, ...]
            
            // Type d'objectif
            $table->enum('type', ['strategic', 'operational', 'improvement', 'compliance'])->default('operational')->after('ref');
            
            // Caractère SMART
            $table->boolean('is_smart_validated')->default(false)->after('type');
            $table->json('smart_criteria')->nullable()->after('is_smart_validated'); // {specific, measurable, achievable, relevant, time_bound}
            
            // Ressources et budget
            $table->decimal('allocated_budget', 10, 2)->nullable()->after('milestones');
            $table->text('required_resources')->nullable()->after('allocated_budget');
        });
    }

    public function down(): void
    {
        Schema::table('objectives', function (Blueprint $table) {
            $table->dropForeign(['workflow_state_id']);
            $table->dropForeign(['indicateur_id']);
            $table->dropColumn([
                'workflow_state_id', 'indicateur_id', 'progress', 'start_date',
                'milestones', 'type', 'is_smart_validated', 'smart_criteria',
                'allocated_budget', 'required_resources'
            ]);
        });
    }
};
