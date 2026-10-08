<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('operational_projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enterprise_id')->nullable()->constrained('enterprises')->nullOnDelete();
            $table->foreignId('site_id')->nullable()->constrained('sites')->nullOnDelete();
            $table->string('code')->nullable()->index();
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('status', ['planned', 'in_progress', 'on_hold', 'completed', 'cancelled'])->default('planned');
            $table->enum('priority', ['low', 'medium', 'high', 'critical'])->default('medium');
            $table->date('start_date')->nullable();
            $table->date('due_date')->nullable();
            $table->integer('progress')->default(0);
            $table->foreignId('project_manager_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('operational_project_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('operational_projects')->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('status', ['planned', 'in_progress', 'on_hold', 'completed', 'cancelled'])->default('planned');
            $table->enum('priority', ['low', 'medium', 'high', 'critical'])->default('medium');
            $table->date('start_date')->nullable();
            $table->date('due_date')->nullable();
            $table->integer('progress')->default(0);
            $table->unsignedInteger('position')->default(0);
            $table->foreignId('responsible_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('operational_project_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('activity_id')->constrained('operational_project_activities')->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('status', ['todo', 'in_progress', 'done', 'blocked'])->default('todo');
            $table->enum('priority', ['low', 'medium', 'high', 'critical'])->default('medium');
            $table->date('start_date')->nullable();
            $table->date('due_date')->nullable();
            $table->integer('progress')->default(0);
            $table->unsignedInteger('position')->default(0);
            $table->foreignId('responsible_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('operational_project_tasks');
        Schema::dropIfExists('operational_project_activities');
        Schema::dropIfExists('operational_projects');
    }
};

