<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fattura_profile', function (Blueprint $table) {
            $table->id();
            $table->string('bezeichnung', 100);
            $table->text('bemerkung')->nullable();
            $table->boolean('split_payment')->default(false);
            $table->boolean('ritenuta')->default(false);
            $table->decimal('mwst_satz', 5, 2)->default(22.00);
            $table->string('code', 30)->nullable()->unique();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['split_payment', 'ritenuta']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fattura_profile');
    }
};
