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
        Schema::table('management_reviews', function (Blueprint $table) {
            // Add new fields for enhanced review tracking
            $table->string('title')->nullable()->after('ref');
            $table->date('scheduled_date')->nullable()->after('title');
            $table->integer('year')->nullable()->after('scheduled_date');
            $table->string('quarter', 10)->nullable()->after('year');
            
            // Enhanced status workflow
            $table->dropColumn('status');
        });
        
        Schema::table('management_reviews', function (Blueprint $table) {
            $table->enum('status', ['planned', 'in_progress', 'completed', 'reported'])->default('planned')->after('quarter');
        });
        
        Schema::table('management_reviews', function (Blueprint $table) {
            // JSON data for metrics
            $table->json('kpi_data')->nullable()->after('status');
            $table->json('objectives_data')->nullable()->after('kpi_data');
            $table->json('actions_data')->nullable()->after('objectives_data');
            $table->json('risks_data')->nullable()->after('actions_data');
            $table->json('nc_data')->nullable()->after('risks_data');
            $table->json('audit_data')->nullable()->after('nc_data');
            $table->json('action_items')->nullable()->after('decisions');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('management_reviews', function (Blueprint $table) {
            $table->dropColumn([
                'title',
                'scheduled_date',
                'year',
                'quarter',
                'kpi_data',
                'objectives_data',
                'actions_data',
                'risks_data',
                'nc_data',
                'audit_data',
                'action_items',
            ]);
            
            $table->dropColumn('status');
        });
        
        Schema::table('management_reviews', function (Blueprint $table) {
            $table->enum('status', ['planned', 'completed'])->default('planned');
        });
    }
};
