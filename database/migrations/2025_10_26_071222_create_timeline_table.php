// Artisan:
php artisan make:migration create_timeline_table

// database/migrations/xxxx_xx_xx_xxxxxx_create_timeline_table.php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('timeline', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('gebaeude_id')->index();
            $table->date('datum')->index();
            $table->text('bemerkung');
            $table->string('person_name', 150);
            $table->unsignedInteger('person_id')->index();
            $table->dateTime('created_at')->useCurrent();

            // FK nur setzen, wenn Tabellen/Typen exakt passen:
            // $table->foreign('gebaeude_id')->references('id')->on('gebaeude')->cascadeOnDelete();
            // $table->foreign('person_id')->references('id')->on('adressen')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('timeline');
    }
};
