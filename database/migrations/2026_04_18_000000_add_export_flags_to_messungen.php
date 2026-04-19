<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('messungen', function (Blueprint $table) {
            $table->timestamp('exported_at')->nullable()->after('codeInImpianti');
            $table->string('exported_to_email', 150)->nullable()->after('exported_at');
            $table->string('exported_kontrolleur_id', 20)->nullable()->after('exported_to_email');

            $table->index('exported_at', 'idx_messungen_exported_at');
        });
    }

    public function down(): void
    {
        Schema::table('messungen', function (Blueprint $table) {
            $table->dropIndex('idx_messungen_exported_at');
            $table->dropColumn(['exported_at', 'exported_to_email', 'exported_kontrolleur_id']);
        });
    }
};
