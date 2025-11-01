<?php
// database/migrations/2025_10_27_000001_add_timestamps_to_timeline_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('timeline', function (Blueprint $table) {
            // Fügt nullable Timestamps hinzu (vermeidet Probleme bei alten Rows)
            $table->timestamp('created_at')->nullable()->after('person_id');
            $table->timestamp('updated_at')->nullable()->after('created_at');
        });
    }

    public function down(): void
    {
        Schema::table('timeline', function (Blueprint $table) {
            $table->dropColumn(['created_at','updated_at']);
        });
    }
};
