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
        // Niveau 1 : Activités du Plan du SM
        Schema::create('sm_plan_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enterprise_id')->constrained()->cascadeOnDelete();
            $table->foreignId('site_id')->nullable()->constrained('sites')->nullOnDelete();
            $table->foreignId('process_id')->nullable()->constrained('processes')->nullOnDelete();
            $table->unsignedInteger('year')->default(date('Y'));
            $table->string('code', 50)->nullable();
            $table->string('title', 255);
            $table->text('description')->nullable();
            $table->unsignedInteger('order')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['enterprise_id', 'year']);
        });

        // Niveau 2 : Sous-activités (Obligatoire selon REQ-6.3-06)
        Schema::create('sm_plan_sub_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('activity_id')->constrained('sm_plan_activities')->cascadeOnDelete();
            $table->string('code', 50)->nullable();
            $table->string('title', 255);
            $table->text('description')->nullable();
            $table->unsignedInteger('order')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('activity_id');
        });

        // Niveau 3 : Actions / Tâches (SANS PONDÉRATION / SANS POIDS selon REQ-6.3-06)
        Schema::create('sm_plan_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sub_activity_id')->constrained('sm_plan_sub_activities')->cascadeOnDelete();
            $table->string('code', 50)->nullable();
            $table->string('title', 255);
            $table->foreignId('responsible_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('responsible_name', 255)->nullable();
            $table->text('internal_actors')->nullable(); // Acteurs internes impliqués
            $table->text('external_actors')->nullable(); // Acteurs externes impliqués
            $table->text('deliverables')->nullable(); // Extrants / Livrables attendus
            $table->text('indicators')->nullable(); // Indicateurs
            $table->date('start_date')->nullable();
            $table->date('deadline')->nullable();
            $table->json('months')->nullable(); // Mois d'exécution (ex: ["Jan", "Fév", "Mar"])
            $table->string('status', 50)->default('a_planifier'); // a_planifier, en_cours, realise, en_retard, replanifie
            $table->text('observations')->nullable();
            
            // Replanification (REQ-6.3-08)
            $table->unsignedInteger('rescheduled_count')->default(0);
            $table->text('rescheduled_reason')->nullable();
            $table->foreignId('rescheduled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('rescheduled_at')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('sub_activity_id');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sm_plan_actions');
        Schema::dropIfExists('sm_plan_sub_activities');
        Schema::dropIfExists('sm_plan_activities');
    }
};

