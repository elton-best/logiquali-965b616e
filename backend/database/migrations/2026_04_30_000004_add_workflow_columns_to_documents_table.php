<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            // Statut du code (pour gestion recyclage)
            $table->enum('code_status', ['active', 'released', 'reserved'])
                ->default('reserved')
                ->after('code');

            // Workflow vérification
            $table->timestamp('verified_at')->nullable()->after('approved_at');
            $table->foreignId('verified_by')
                ->nullable()
                ->after('verified_at')
                ->constrained('users')
                ->onDelete('set null');

            // Index pour performance
            $table->index('code_status');
            $table->index(['workflow_status', 'code_status']);
        });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropForeign(['verified_by']);
            $table->dropIndex(['code_status']);
            $table->dropIndex(['workflow_status', 'code_status']);
            $table->dropColumn([
                'code_status',
                'verified_at',
                'verified_by'
            ]);
        });
    }
};
