<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stakeholders', function (Blueprint $table) {
            if (!Schema::hasColumn('stakeholders', 'contact_person')) {
                $table->string('contact_person')->nullable()->after('actions');
            }
            if (!Schema::hasColumn('stakeholders', 'contact_email')) {
                $table->string('contact_email')->nullable()->after('contact_person');
            }
        });
    }

    public function down(): void
    {
        Schema::table('stakeholders', function (Blueprint $table) {
            if (Schema::hasColumn('stakeholders', 'contact_email')) {
                $table->dropColumn('contact_email');
            }
            if (Schema::hasColumn('stakeholders', 'contact_person')) {
                $table->dropColumn('contact_person');
            }
        });
    }
};
