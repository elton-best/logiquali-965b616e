<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('modules', function (Blueprint $table) {
            if (!Schema::hasColumn('modules', 'is_common')) {
                $table->boolean('is_common')->default(false)->after('is_active');
            }
        });

        Schema::table('sub_modules', function (Blueprint $table) {
            if (!Schema::hasColumn('sub_modules', 'is_common')) {
                $table->boolean('is_common')->default(false)->after('is_active');
            }
        });

        Schema::table('sub_module_sections', function (Blueprint $table) {
            if (!Schema::hasColumn('sub_module_sections', 'is_common')) {
                $table->boolean('is_common')->default(false)->after('is_active');
            }
        });
    }

    public function down(): void
    {
        Schema::table('sub_module_sections', function (Blueprint $table) {
            if (Schema::hasColumn('sub_module_sections', 'is_common')) {
                $table->dropColumn('is_common');
            }
        });

        Schema::table('sub_modules', function (Blueprint $table) {
            if (Schema::hasColumn('sub_modules', 'is_common')) {
                $table->dropColumn('is_common');
            }
        });

        Schema::table('modules', function (Blueprint $table) {
            if (Schema::hasColumn('modules', 'is_common')) {
                $table->dropColumn('is_common');
            }
        });
    }
};

