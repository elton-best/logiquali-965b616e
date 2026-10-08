<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('application_scopes', function (Blueprint $table) {
            $table->text('objective')->nullable()->change();
            $table->text('scope')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('application_scopes', function (Blueprint $table) {
            $table->text('objective')->nullable(false)->change();
            $table->text('scope')->nullable(false)->change();
        });
    }
};
