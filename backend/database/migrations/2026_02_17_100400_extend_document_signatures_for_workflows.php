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
        Schema::table('document_signatures', function (Blueprint $table) {
            // Lien vers workflow (nullable pour signatures simples)
            $table->foreignId('workflow_id')
                ->nullable()
                ->after('id')
                ->constrained('document_signature_workflows')
                ->onDelete('cascade');

            // Ordre dans le workflow
            $table->integer('signature_order')->nullable()->after('workflow_id');

            // Rôle requis pour signer
            $table->string('role_required', 50)->nullable()->after('signature_order');

            // Statut signature
            $table->enum('status', ['pending', 'signed', 'rejected'])
                ->default('pending')
                ->after('signed_at');

            // Commentaires et rejet
            $table->text('comments')->nullable()->after('status');
            $table->text('rejection_reason')->nullable()->after('revocation_reason');

            // Index
            $table->index('workflow_id');
            $table->index('status');
            $table->index(['workflow_id', 'signature_order']);
            
            // Contrainte unicité : 1 signature par ordre dans workflow
            $table->unique(['workflow_id', 'signature_order'], 'unique_workflow_order');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('document_signatures', function (Blueprint $table) {
            $table->dropForeign(['workflow_id']);
            $table->dropUnique('unique_workflow_order');
            $table->dropIndex(['workflow_id', 'signature_order']);
            $table->dropIndex(['status']);
            $table->dropIndex(['workflow_id']);
            
            $table->dropColumn([
                'workflow_id',
                'signature_order',
                'role_required',
                'status',
                'comments',
                'rejection_reason',
            ]);
        });
    }
};
