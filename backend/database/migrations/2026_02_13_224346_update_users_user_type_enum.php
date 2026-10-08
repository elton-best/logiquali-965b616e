<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Supprimer contrainte CHECK existante
        DB::statement("ALTER TABLE users DROP CONSTRAINT IF EXISTS users_user_type_check");
        
        // Migrer anciennes valeurs
        DB::statement("UPDATE users SET user_type = 'company' WHERE user_type = 'entreprise'");
        
        // Recréer contrainte avec nouvelles valeurs
        DB::statement("
            ALTER TABLE users ADD CONSTRAINT users_user_type_check 
            CHECK (user_type IN ('super_admin', 'admin', 'company', 'clientb', 'user', 'collaborator'))
        ");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE users DROP CONSTRAINT IF EXISTS users_user_type_check");
        DB::statement("UPDATE users SET user_type = 'entreprise' WHERE user_type = 'company'");
        DB::statement("
            ALTER TABLE users ADD CONSTRAINT users_user_type_check 
            CHECK (user_type IN ('admin', 'entreprise', 'user', 'collaborator'))
        ");
    }
};
