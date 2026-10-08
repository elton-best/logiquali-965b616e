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
        Schema::table('documents', function (Blueprint $table) {
            // Catégorie pyramide QHSE
            $table->foreignId('category_id')->nullable()->after('site_id')
                ->constrained('document_categories')->nullOnDelete();
            
            // Rétention et archivage
            $table->integer('retention_period_years')->nullable()->after('is_active');
            $table->timestamp('archived_at')->nullable()->after('retention_period_years');
            $table->foreignId('archived_by')->nullable()->after('archived_at')
                ->constrained('users')->nullOnDelete();
            $table->text('archive_reason')->nullable()->after('archived_by');
            
            // Diffusion et révision
            $table->timestamp('published_at')->nullable()->after('approved_at');
            $table->timestamp('effective_date')->nullable()->after('published_at'); // Date application
            $table->timestamp('review_due_date')->nullable()->after('effective_date'); // Prochaine révision
            
            // Métadonnées (description déjà existe normalement)
            $table->text('keywords')->nullable()->after('title'); // Mots-clés pour recherche
            $table->string('language', 5)->default('fr')->after('keywords');
            $table->boolean('is_confidential')->default(false)->after('is_active');
            $table->enum('confidentiality_level', ['public', 'internal', 'confidential', 'restricted'])
                ->default('internal')->after('is_confidential');
            
            // Index pour optimisation recherche
            $table->index('category_id');
            $table->index('status');
            $table->index(['site_id', 'category_id']);
            $table->index('archived_at');
            $table->index('is_confidential');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropForeign(['category_id']);
            $table->dropForeign(['archived_by']);
            
            $table->dropIndex(['category_id']);
            $table->dropIndex(['status']);
            $table->dropIndex(['site_id', 'category_id']);
            $table->dropIndex(['archived_at']);
            $table->dropIndex(['is_confidential']);
            
            $table->dropColumn([
                'category_id',
                'retention_period_years',
                'archived_at',
                'archived_by',
                'archive_reason',
                'published_at',
                'effective_date',
                'review_due_date',
                'keywords',
                'language',
                'is_confidential',
                'confidentiality_level',
            ]);
        });
    }
};
