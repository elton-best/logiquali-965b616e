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
        Schema::create('stakeholders', function (Blueprint $table) {
            $table->id();
            $table->string('ref')->unique();
            $table->foreignId('site_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->enum('type', ['client', 'supplier', 'partner', 'regulator', 'employee', 'shareholder', 'other']);
            $table->enum('relevance_degree', ['low', 'high', 'medium'])->default('medium');
            $table->text('needs_expectations')->nullable();
            $table->text('requirements')->nullable();
            $table->text('actions')->nullable();
            $table->foreignId('responsible_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('deadline')->nullable();


            // $table->enum('importance', ['high', 'medium', 'low'])->default('medium');
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
        Schema::dropIfExists('stakeholders');
    }
};
