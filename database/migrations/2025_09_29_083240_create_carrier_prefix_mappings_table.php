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
        Schema::create('carrier_prefix_mappings', function (Blueprint $table) {
            $table->id();
            $table->string('country_code', 5);
            $table->char('iso2', 2);
            $table->string('prefix', 5);
            $table->string('carrier_keyword', 50);
            $table->timestamps();

            $table->index(['country_code', 'prefix'], 'idx_country_prefix');
            $table->index('iso2', 'idx_iso2');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('carrier_prefix_mappings');
    }
};
