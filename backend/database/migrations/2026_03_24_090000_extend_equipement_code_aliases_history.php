<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('equipement_code_aliases', function (Blueprint $table) {
            $table->foreignId('changed_by')->nullable()->after('enterprise_id')->constrained('users')->nullOnDelete();
            $table->timestamp('effective_from')->nullable()->after('code_alias');
            $table->timestamp('effective_to')->nullable()->after('effective_from');
            $table->boolean('is_primary_at_time')->default(false)->after('effective_to');
            $table->string('notes')->nullable()->after('is_primary_at_time');

            $table->index(['equipement_id', 'effective_from'], 'equip_code_aliases_effective_from_idx');
            $table->index(['equipement_id', 'is_primary_at_time'], 'equip_code_aliases_primary_idx');
        });
    }

    public function down(): void
    {
        Schema::table('equipement_code_aliases', function (Blueprint $table) {
            $table->dropIndex('equip_code_aliases_effective_from_idx');
            $table->dropIndex('equip_code_aliases_primary_idx');
            $table->dropForeign(['changed_by']);
            $table->dropColumn([
                'changed_by',
                'effective_from',
                'effective_to',
                'is_primary_at_time',
                'notes',
            ]);
        });
    }
};
