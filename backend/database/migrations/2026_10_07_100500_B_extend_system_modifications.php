<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('modifications', function (Blueprint $table): void {
            $table->foreignId('initiator_id')->nullable()->after('site_id')->constrained('users')->nullOnDelete();
            $table->text('scope')->nullable()->after('description');
            $table->jsonb('document_ids')->nullable()->after('required_resources');
            $table->foreignId('result_owner_id')->nullable()->after('responsible_id')->constrained('users')->nullOnDelete();
            $table->foreignId('monitoring_owner_id')->nullable()->after('result_owner_id')->constrained('users')->nullOnDelete();
            $table->string('workflow_status', 40)->default('brouillon')->after('status');
            $table->string('approved_version', 80)->nullable()->after('workflow_status');
            $table->timestamp('approved_at')->nullable()->after('approved_version');
            $table->index(['site_id', 'workflow_status'], 'modifications_workflow_scope_idx');
        });
    }

    public function down(): void
    {
        Schema::table('modifications', function (Blueprint $table): void {
            $table->dropIndex('modifications_workflow_scope_idx');
            $table->dropForeign(['initiator_id']);
            $table->dropForeign(['result_owner_id']);
            $table->dropForeign(['monitoring_owner_id']);
            $table->dropColumn([
                'initiator_id',
                'scope',
                'document_ids',
                'result_owner_id',
                'monitoring_owner_id',
                'workflow_status',
                'approved_version',
                'approved_at',
            ]);
        });
    }
};
