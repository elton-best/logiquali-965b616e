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
        Schema::create('process_interactions', function (Blueprint $table) {
            $table->id();
            $table->string('ref')->unique();
            $table->foreignId('supplier_process_id')->constrained('processes')->cascadeOnDelete();
            $table->foreignId('self_process_id')->constrained('processes')->cascadeOnDelete();
            $table->foreignId('client_process_id')->constrained('processes')->cascadeOnDelete();
            $table->text('description')->nullable();

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
        Schema::dropIfExists('process_interactions');
    }
};
