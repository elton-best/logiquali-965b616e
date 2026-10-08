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
        Schema::create('process_resources', function (Blueprint $table) {
            $table->id();
            $table->string('ref')->unique();
            $table->foreignId('process_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['human', 'technological', 'documentary', 'material']);
            $table->text('description');
            $table->string('quantity')->nullable();
            $table->boolean('is_available')->default(true);
            
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
        Schema::dropIfExists('process_resources');
    }
};
