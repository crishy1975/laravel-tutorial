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
        Schema::table('gebaeude_dokumente', function (Blueprint $table) {
            // MIME-Types können sehr lang sein (z.B. application/vnd.openxmlformats-officedocument.spreadsheetml.sheet = 71 Zeichen)
            $table->string('dateityp', 150)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('gebaeude_dokumente', function (Blueprint $table) {
            $table->string('dateityp', 50)->change();
        });
    }
};
