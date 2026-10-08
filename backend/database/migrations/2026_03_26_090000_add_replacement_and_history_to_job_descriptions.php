<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('job_descriptions', 'replacement_job_title')) {
            Schema::table('job_descriptions', function (Blueprint $table) {
                $table->string('replacement_job_title', 255)
                    ->nullable()
                    ->after('job_title');
            });
        }

        if (!Schema::hasTable('job_description_histories')) {
            Schema::create('job_description_histories', function (Blueprint $table) {
                $table->id();
                $table->foreignId('job_description_id')
                    ->constrained('job_descriptions')
                    ->cascadeOnDelete();
                $table->foreignId('enterprise_id')
                    ->nullable()
                    ->constrained('enterprises')
                    ->nullOnDelete();
                $table->foreignId('site_id')
                    ->nullable()
                    ->constrained('sites')
                    ->nullOnDelete();
                $table->string('field_key', 120);
                $table->text('old_value')->nullable();
                $table->text('new_value')->nullable();
                $table->foreignId('changed_by')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();
                $table->string('change_source', 120)->nullable();
                $table->timestamp('changed_at');
                $table->timestamps();

                $table->index(['job_description_id', 'field_key']);
                $table->index(['job_description_id', 'changed_at']);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('job_description_histories')) {
            Schema::dropIfExists('job_description_histories');
        }

        if (Schema::hasColumn('job_descriptions', 'replacement_job_title')) {
            Schema::table('job_descriptions', function (Blueprint $table) {
                $table->dropColumn('replacement_job_title');
            });
        }
    }
};
