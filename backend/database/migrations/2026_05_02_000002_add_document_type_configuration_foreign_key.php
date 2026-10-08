<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        try {
            Schema::table('documents', function (Blueprint $table) {
                $table->foreign('document_type_configuration_id')
                    ->references('id')
                    ->on('document_type_configurations')
                    ->nullOnDelete();
            });
        } catch (QueryException $e) {
            // Idempotent: ignorer si la contrainte existe déjà.
            if (!str_contains((string) $e->getMessage(), 'already exists')) {
                throw $e;
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropForeign(['document_type_configuration_id']);
        });
    }
};
