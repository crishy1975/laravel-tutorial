<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('eingangsrechnungen', function (Blueprint $table) {
            $table->string('tipo_documento', 10)->default('TD01')->after('dateiname');
        });
    }

    public function down(): void
    {
        Schema::table('eingangsrechnungen', function (Blueprint $table) {
            $table->dropColumn('tipo_documento');
        });
    }
};
