<?php
// database/migrations/2025_10_24_100000_add_fields_to_tour_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('tour', function (Blueprint $table) {
            if (!Schema::hasColumn('tour', 'reihenfolge')) {
                $table->unsignedInteger('reihenfolge')->default(0)->index()->after('id');
            }
            if (!Schema::hasColumn('tour', 'aktiv')) {
                $table->boolean('aktiv')->default(true)->index()->after('reihenfolge');
            }
            if (!Schema::hasColumn('tour', 'beschreibung')) {
                $table->text('beschreibung')->nullable()->after('name');
            }
            if (!Schema::hasColumn('tour', 'deleted_at')) {
                $table->softDeletes(); // für SoftDeletes Trait
            }
        });
    }

    public function down(): void {
        Schema::table('tour', function (Blueprint $table) {
            if (Schema::hasColumn('tour', 'reihenfolge')) $table->dropColumn('reihenfolge');
            if (Schema::hasColumn('tour', 'aktiv'))       $table->dropColumn('aktiv');
            if (Schema::hasColumn('tour', 'beschreibung'))$table->dropColumn('beschreibung');
            if (Schema::hasColumn('tour', 'deleted_at'))  $table->dropSoftDeletes();
        });
    }
};
