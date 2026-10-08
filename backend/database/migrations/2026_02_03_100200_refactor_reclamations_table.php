<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reclamations', function (Blueprint $table) {
            // Ajuster stakeholder_type pour se concentrer sur clients externes
            $table->dropColumn('stakeholder_type');
        });
        
        Schema::table('reclamations', function (Blueprint $table) {
            $table->enum('stakeholder_type', ['client', 'supplier', 'distributor', 'end_user', 'other'])->default('client')->after('client_company');
            
            // Ajuster catégories pour se concentrer sur produits/services
            $table->dropColumn('category');
        });
        
        Schema::table('reclamations', function (Blueprint $table) {
            $table->enum('category', ['product_quality', 'product_defect', 'service_quality', 'delivery_delay', 'delivery_error', 'documentation', 'packaging', 'billing', 'communication', 'other'])->nullable()->after('stakeholder_type');
            
            // Ajouter champs spécifiques réclamations clients
            $table->string('product_ref')->nullable()->after('category');
            $table->string('batch_number')->nullable()->after('product_ref');
            $table->string('order_number')->nullable()->after('batch_number');
            $table->date('incident_date')->nullable()->after('order_number');
            $table->integer('affected_quantity')->nullable()->after('incident_date');
            
            // Type de résolution
            $table->enum('resolution_type', ['refund', 'replacement', 'repair', 'credit_note', 'discount', 'apology', 'explanation', 'other'])->nullable()->after('immediate_response');
            $table->decimal('refund_amount', 10, 2)->nullable()->after('resolution_type');
        });
    }

    public function down(): void
    {
        Schema::table('reclamations', function (Blueprint $table) {
            $table->dropColumn([
                'product_ref', 'batch_number', 'order_number', 'incident_date',
                'affected_quantity', 'resolution_type', 'refund_amount'
            ]);
            
            $table->dropColumn('stakeholder_type');
            $table->dropColumn('category');
        });
        
        Schema::table('reclamations', function (Blueprint $table) {
            $table->enum('stakeholder_type', ['client', 'supplier', 'employee', 'authority', 'other'])->default('client');
            $table->enum('category', ['product', 'service', 'delivery', 'quality', 'safety', 'environment', 'other'])->nullable();
        });
    }
};
