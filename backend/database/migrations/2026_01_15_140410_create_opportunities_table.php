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
        Schema::create('opportunities', function (Blueprint $table) {
            $table->id();
            $table->string('ref')->unique();
            $table->foreignId('process_id')->constrained()->cascadeOnDelete();
            $table->foreignId('site_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('description');
            $table->text('exploitation_actions')->nullable();

            $table->foreignId('responsible_id')->constrained('users')->nullOnDelete();
            $table->date('deadline')->nullable();
            $table->enum('status', ['identified', 'evaluated', 'exploited', 'monitored'])->default('identified');


            // $table->enum('type', ['internal', 'external']);
            // $table->integer('potential')->default(1);
            // $table->integer('feasibility')->default(1);
            // $table->integer('priority')->storedAs('potential * feasibility');
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
        Schema::dropIfExists('opportunities');
    }
};
