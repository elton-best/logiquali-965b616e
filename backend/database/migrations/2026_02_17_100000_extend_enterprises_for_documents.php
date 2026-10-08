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
        Schema::table('enterprises', function (Blueprint $table) {
            // === IDENTITÉ CORPORATE ===
            $table->string('slogan')->nullable()->after('name');
            $table->json('brand_colors')->nullable()->after('slogan');
            $table->string('header_font', 50)->default('Arial')->after('brand_colors');

            // === LÉGAL BÉNIN ===
            $table->string('rccm_number', 100)->nullable()->after('registration_number');
            $table->string('ifu_number', 50)->nullable()->after('rccm_number');
            $table->string('cnss_number', 50)->nullable()->after('ifu_number');
            $table->string('fodefca_number', 50)->nullable()->after('cnss_number');

            // === LÉGAL FRANCE ===
            $table->string('siret', 50)->nullable()->after('fodefca_number');
            $table->string('rcs', 100)->nullable()->after('siret');
            $table->string('vat_number', 50)->nullable()->after('rcs');
            $table->string('ape_code', 20)->nullable()->after('vat_number');

            // === LÉGAL GÉNÉRAL ===
            $table->string('legal_form', 50)->nullable()->after('ape_code');
            $table->string('share_capital', 100)->nullable()->after('legal_form');
            $table->json('legal_profile_config')->nullable()->after('share_capital');

            // === COORDONNÉES ÉTENDUES ===
            $table->string('address_line_1')->nullable()->after('address');
            $table->string('address_line_2')->nullable()->after('address_line_1');
            $table->string('postal_code', 50)->nullable()->after('address_line_2');
            $table->string('phone_primary', 50)->nullable()->after('phone');
            $table->string('phone_secondary', 50)->nullable()->after('phone_primary');
            $table->json('phone_types')->nullable()->after('phone_secondary');
            $table->string('email_general')->nullable()->after('email');
            $table->string('email_support')->nullable()->after('email_general');
            $table->string('website')->nullable()->after('email_support');

            // === RÉSEAUX SOCIAUX ===
            $table->json('social_media')->nullable()->after('website');

            // === CONFIGURATION DOCUMENTS ===
            $table->json('header_footer_config')->nullable()->after('social_media');

            // Index pour recherches
            $table->index('country');
            $table->index('legal_form');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('enterprises', function (Blueprint $table) {
            $table->dropIndex(['country']);
            $table->dropIndex(['legal_form']);

            $table->dropColumn([
                'slogan',
                'brand_colors',
                'header_font',
                'rccm_number',
                'ifu_number',
                'cnss_number',
                'fodefca_number',
                'siret',
                'rcs',
                'vat_number',
                'ape_code',
                'legal_form',
                'share_capital',
                'legal_profile_config',
                'address_line_1',
                'address_line_2',
                'postal_code',
                'phone_primary',
                'phone_secondary',
                'phone_types',
                'email_general',
                'email_support',
                'website',
                'social_media',
                'header_footer_config',
            ]);
        });
    }
};
