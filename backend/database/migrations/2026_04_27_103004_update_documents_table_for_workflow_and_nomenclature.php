<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Ajouter les champs polymorphiques et spécifiques au workflow dans documents
        Schema::table('documents', function (Blueprint $table) {
            if (!Schema::hasColumn('documents', 'module_type')) {
                $table->nullableMorphs('module'); // module_type, module_id
            }
            if (!Schema::hasColumn('documents', 'needs_verification')) {
                $table->boolean('needs_verification')->default(true);
            }
            if (!Schema::hasColumn('documents', 'verifier_id')) {
                $table->foreignId('verifier_id')->nullable()->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('documents', 'rejection_reason')) {
                $table->text('rejection_reason')->nullable();
            }
            
            if (!Schema::hasColumn('documents', 'workflow_status')) {
                $table->string('workflow_status')->default('draft');
            }
        });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropMorphs('module');
            $table->dropColumn('needs_verification');
            $table->dropConstrainedForeignId('verifier_id');
            $table->dropColumn('rejection_reason');
            $table->dropColumn('workflow_status');
        });
    }
};
