<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('permission_norm_mappings', function (Blueprint $table): void {
            if (!Schema::hasColumn('permission_norm_mappings', 'sub_module_id')) {
                $table->unsignedBigInteger('sub_module_id')->nullable()->after('module_id');
                $table->foreign('sub_module_id')
                    ->references('id')
                    ->on('sub_modules')
                    ->nullOnDelete();
                $table->index('sub_module_id');
            }

            if (!Schema::hasColumn('permission_norm_mappings', 'section_id')) {
                $table->unsignedBigInteger('section_id')->nullable()->after('sub_module_id');
                $table->foreign('section_id')
                    ->references('id')
                    ->on('sub_module_sections')
                    ->nullOnDelete();
                $table->index('section_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('permission_norm_mappings', function (Blueprint $table): void {
            if (Schema::hasColumn('permission_norm_mappings', 'section_id')) {
                $table->dropForeign(['section_id']);
                $table->dropIndex(['section_id']);
                $table->dropColumn('section_id');
            }

            if (Schema::hasColumn('permission_norm_mappings', 'sub_module_id')) {
                $table->dropForeign(['sub_module_id']);
                $table->dropIndex(['sub_module_id']);
                $table->dropColumn('sub_module_id');
            }
        });
    }
};

