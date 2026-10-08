<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('risks', function (Blueprint $table) {
            // Ajouter type (risk/opportunity) si pas déjà fait
            if (!Schema::hasColumn('risks', 'type')) {
                $table->enum('type', ['risk', 'opportunity'])->default('risk')->after('site_id');
            }
            
            // Ajouter catégorie et source
            if (!Schema::hasColumn('risks', 'category')) {
                $table->string('category', 100)->nullable()->after('description');
            }
            if (!Schema::hasColumn('risks', 'source')) {
                $table->string('source', 100)->nullable()->after('category');
            }
            
            // Ajouter stratégie de traitement
            if (!Schema::hasColumn('risks', 'treatment_strategy')) {
                $table->string('treatment_strategy', 50)->nullable()->after('control_action');
            }
            if (!Schema::hasColumn('risks', 'treatment_plan')) {
                $table->text('treatment_plan')->nullable()->after('treatment_strategy');
            }
            
            // Ajouter risque résiduel
            if (!Schema::hasColumn('risks', 'residual_probability')) {
                $table->integer('residual_probability')->nullable()->after('treatment_plan');
            }
            if (!Schema::hasColumn('risks', 'residual_impact')) {
                $table->integer('residual_impact')->nullable()->after('residual_probability');
            }
            
            // Ajouter fréquence de revue
            if (!Schema::hasColumn('risks', 'review_frequency')) {
                $table->string('review_frequency', 50)->nullable()->after('deadline');
            }
            if (!Schema::hasColumn('risks', 'review_date')) {
                $table->date('review_date')->nullable()->after('review_frequency');
            }
            
            // Ajouter validation
            if (!Schema::hasColumn('risks', 'validated_by')) {
                $table->foreignId('validated_by')->nullable()->after('created_by')->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('risks', 'validated_at')) {
                $table->timestamp('validated_at')->nullable()->after('validated_by');
            }
        });
    }

    public function down(): void
    {
        Schema::table('risks', function (Blueprint $table) {
            $columns = [
                'type', 'category', 'source', 'treatment_strategy', 'treatment_plan',
                'residual_probability', 'residual_impact',
                'review_frequency', 'review_date', 'validated_by', 'validated_at'
            ];
            
            foreach ($columns as $column) {
                if (Schema::hasColumn('risks', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
