<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Supprimer l'ancienne contrainte CHECK
        DB::statement('ALTER TABLE users DROP CONSTRAINT IF EXISTS users_user_type_check');
        
        // Créer la nouvelle contrainte CHECK avec les nouveaux types
        DB::statement("
            ALTER TABLE users 
            ADD CONSTRAINT users_user_type_check 
            CHECK (user_type::text = ANY (ARRAY[
                'admin'::character varying,
                'entreprise'::character varying,
                'user'::character varying,
                'collaborator'::character varying,
                'client'::character varying,
                'clientb'::character varying,
                'clienta'::character varying,
                'superadmin'::character varying
            ]::text[]))
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Restaurer l'ancienne contrainte CHECK
        DB::statement('ALTER TABLE users DROP CONSTRAINT IF EXISTS users_user_type_check');
        
        DB::statement("
            ALTER TABLE users 
            ADD CONSTRAINT users_user_type_check 
            CHECK (user_type::text = ANY (ARRAY[
                'admin'::character varying,
                'entreprise'::character varying,
                'user'::character varying,
                'collaborator'::character varying
            ]::text[]))
        ");
    }
};
