<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gebaeude', function (Blueprint $table) {
            $table->date('angekuendigt_fuer')->nullable()->after('faellig');
            $table->timestamp('angekuendigt_am')->nullable()->after('angekuendigt_fuer');
        });
    }

    public function down(): void
    {
        Schema::table('gebaeude', function (Blueprint $table) {
            $table->dropColumn(['angekuendigt_fuer', 'angekuendigt_am']);
        });
    }
};
