<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('strassen_mappings', function (Blueprint $table) {
            $table->id();
            $table->string('strasse_original')->unique();
            $table->string('sort_key')->index();
            $table->boolean('is_manual')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('strassen_mappings');
    }
};
