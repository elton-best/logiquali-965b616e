<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'job_title')) {
                $table->string('job_title')->nullable()->after('role');
            }
            if (!Schema::hasColumn('users', 'start_date')) {
                $table->date('start_date')->nullable()->after('job_title');
            }
            if (!Schema::hasColumn('users', 'address')) {
                $table->string('address')->nullable()->after('phone');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $columns = [];
            if (Schema::hasColumn('users', 'job_title')) {
                $columns[] = 'job_title';
            }
            if (Schema::hasColumn('users', 'start_date')) {
                $columns[] = 'start_date';
            }
            if (Schema::hasColumn('users', 'address')) {
                $columns[] = 'address';
            }

            if (!empty($columns)) {
                $table->dropColumn($columns);
            }
        });
    }
};

