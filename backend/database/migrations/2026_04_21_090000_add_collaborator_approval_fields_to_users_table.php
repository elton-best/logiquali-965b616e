<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            if (!Schema::hasColumn('users', 'collaborator_approval_status')) {
                $table->string('collaborator_approval_status', 32)
                    ->default('approved')
                    ->after('is_active');
            }
            if (!Schema::hasColumn('users', 'collaborator_requested_by')) {
                $table->foreignId('collaborator_requested_by')
                    ->nullable()
                    ->after('collaborator_approval_status')
                    ->constrained('users')
                    ->nullOnDelete();
            }
            if (!Schema::hasColumn('users', 'collaborator_requested_at')) {
                $table->timestamp('collaborator_requested_at')
                    ->nullable()
                    ->after('collaborator_requested_by');
            }
            if (!Schema::hasColumn('users', 'collaborator_approved_by')) {
                $table->foreignId('collaborator_approved_by')
                    ->nullable()
                    ->after('collaborator_requested_at')
                    ->constrained('users')
                    ->nullOnDelete();
            }
            if (!Schema::hasColumn('users', 'collaborator_approved_at')) {
                $table->timestamp('collaborator_approved_at')
                    ->nullable()
                    ->after('collaborator_approved_by');
            }
            if (!Schema::hasColumn('users', 'collaborator_rejected_by')) {
                $table->foreignId('collaborator_rejected_by')
                    ->nullable()
                    ->after('collaborator_approved_at')
                    ->constrained('users')
                    ->nullOnDelete();
            }
            if (!Schema::hasColumn('users', 'collaborator_rejected_at')) {
                $table->timestamp('collaborator_rejected_at')
                    ->nullable()
                    ->after('collaborator_rejected_by');
            }
            if (!Schema::hasColumn('users', 'collaborator_rejection_reason')) {
                $table->text('collaborator_rejection_reason')
                    ->nullable()
                    ->after('collaborator_rejected_at');
            }
        });

        DB::table('users')
            ->whereNull('collaborator_approval_status')
            ->update(['collaborator_approval_status' => 'approved']);

        DB::table('users')
            ->where('user_type', 'company')
            ->where('is_active', false)
            ->whereNull('collaborator_approved_at')
            ->update(['collaborator_approval_status' => 'pending_admin_approval']);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            if (Schema::hasColumn('users', 'collaborator_requested_by')) {
                $table->dropConstrainedForeignId('collaborator_requested_by');
            }
            if (Schema::hasColumn('users', 'collaborator_approved_by')) {
                $table->dropConstrainedForeignId('collaborator_approved_by');
            }
            if (Schema::hasColumn('users', 'collaborator_rejected_by')) {
                $table->dropConstrainedForeignId('collaborator_rejected_by');
            }

            $columns = [];
            foreach ([
                'collaborator_approval_status',
                'collaborator_requested_at',
                'collaborator_approved_at',
                'collaborator_rejected_at',
                'collaborator_rejection_reason',
            ] as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $columns[] = $column;
                }
            }

            if (!empty($columns)) {
                $table->dropColumn($columns);
            }
        });
    }
};
