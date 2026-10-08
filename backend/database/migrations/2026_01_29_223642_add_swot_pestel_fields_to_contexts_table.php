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
        Schema::table('contexts', function (Blueprint $table) {
            // Add year for annual analysis
            $table->integer('year')->nullable()->after('ref');
            $table->string('title')->nullable()->after('year');
            
            // SWOT fields
            $table->text('swot_strengths')->nullable()->after('analysis');
            $table->text('swot_weaknesses')->nullable()->after('swot_strengths');
            $table->text('swot_opportunities')->nullable()->after('swot_weaknesses');
            $table->text('swot_threats')->nullable()->after('swot_opportunities');
            
            // PESTEL fields
            $table->text('pestel_political')->nullable()->after('swot_threats');
            $table->text('pestel_economic')->nullable()->after('pestel_political');
            $table->text('pestel_social')->nullable()->after('pestel_economic');
            $table->text('pestel_technological')->nullable()->after('pestel_social');
            $table->text('pestel_environmental')->nullable()->after('pestel_technological');
            $table->text('pestel_legal')->nullable()->after('pestel_environmental');
            
            // Make type nullable to support swot/pestel types
            $table->string('type', 50)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contexts', function (Blueprint $table) {
            $table->dropColumn([
                'year',
                'title',
                'swot_strengths',
                'swot_weaknesses',
                'swot_opportunities',
                'swot_threats',
                'pestel_political',
                'pestel_economic',
                'pestel_social',
                'pestel_technological',
                'pestel_environmental',
                'pestel_legal',
            ]);
        });
    }
};
