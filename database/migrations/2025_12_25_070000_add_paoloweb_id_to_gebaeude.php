<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * paoloWeb Import-ID
     */
    public function up(): void
    {
        Schema::table('gebaeude', function (Blueprint $table) {
            $table->unsignedInteger('paoloweb_id')->nullable()->after('legacy_mid')->comment('ID aus paoloWeb Import');
            $table->index('paoloweb_id');
        });
    }

    public function down(): void
    {
        Schema::table('gebaeude', function (Blueprint $table) {
            $table->dropIndex(['paoloweb_id']);
            $table->dropColumn('paoloweb_id');
        });
    }
};
