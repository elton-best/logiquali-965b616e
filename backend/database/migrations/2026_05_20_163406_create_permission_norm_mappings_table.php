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
        Schema::create('permission_norm_mappings', function (Blueprint $table) {
            $table->id();
            
            // Foreign keys
            $table->unsignedBigInteger('permission_id');
            $table->unsignedBigInteger('norm_id');
            $table->unsignedBigInteger('module_id');
            
            // Metadata
            $table->boolean('is_shared')->default(false);
            $table->text('description')->nullable();
            
            // Timestamps
            $table->timestamps();
            
            // Foreign key constraints
            $table->foreign('permission_id')
                ->references('id')
                ->on('permissions')
                ->onDelete('cascade');
            
            $table->foreign('norm_id')
                ->references('id')
                ->on('norms')
                ->onDelete('cascade');
            
            $table->foreign('module_id')
                ->references('id')
                ->on('modules')
                ->onDelete('cascade');
            
            // Compound unique index to prevent duplicate mappings (permission + norm)
            $table->unique(['permission_id', 'norm_id']);
            
            // Indexes for queries
            $table->index(['norm_id']);
            $table->index(['module_id']);
            $table->index(['is_shared']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('permission_norm_mappings');
    }
};
