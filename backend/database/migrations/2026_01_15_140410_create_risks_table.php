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
        Schema::create('risks', function (Blueprint $table) {
            $table->id();
            $table->string('ref')->unique();
            $table->foreignId('process_id')->constrained()->cascadeOnDelete();
            $table->foreignId('site_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('description');
            $table->text('root_cause')->nullable();
            $table->integer('probability')->default(1);
            $table->integer('severity')->default(1);
            $table->integer('criticality')->storedAs('probability * severity');
            $table->text('control_action')->nullable();
            $table->foreignId('responsible_id')->constrained('users')->nullOnDelete();
            $table->date('deadline')->nullable();
            $table->enum('status', ['identified', 'evaluated', 'treated', 'monitored'])->default('identified');



            // $table->enum('type', ['internal', 'external']);
            // $table->enum('category', ['strategic', 'operational', 'financial', 'compliance', 'other']);
            // $table->integer('impact')->default(1);
            $table->timestamps();
            $table->foreignId('created_by')->nullable();
            $table->foreignId('updated_by')->nullable();
            $table->foreignId('deleted_by')->nullable();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('risks');
    }
};
