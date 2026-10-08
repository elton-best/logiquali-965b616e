<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('enterprises', function (Blueprint $table) {
            if (!Schema::hasColumn('enterprises', 'approval_status')) {
                $table->enum('approval_status', ['pending', 'approved', 'rejected'])->default('pending')->after('status');
            }
            if (!Schema::hasColumn('enterprises', 'approved_by')) {
                $table->foreignId('approved_by')->nullable()->after('approval_status')->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('enterprises', 'approved_at')) {
                $table->timestamp('approved_at')->nullable()->after('approved_by');
            }
            if (!Schema::hasColumn('enterprises', 'rejection_reason')) {
                $table->text('rejection_reason')->nullable()->after('approved_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('enterprises', function (Blueprint $table) {
            $columns = ['approval_status', 'approved_by', 'approved_at', 'rejection_reason'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('enterprises', $column)) {
                    if ($column === 'approved_by') {
                        $table->dropForeign(['approved_by']);
                    }
                    $table->dropColumn($column);
                }
            }
        });
    }
};
