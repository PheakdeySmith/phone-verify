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
        Schema::create('tmt_coverage', function (Blueprint $table) {
            $table->id();
            $table->string('iso2', 2);
            $table->string('network_id');
            $table->string('network_name');
            $table->string('mcc', 3);
            $table->string('mnc', 3);
            $table->string('country_code');
            $table->string('prefix');
            $table->boolean('live_coverage')->default(true);
            $table->decimal('rate', 10, 6);
            $table->timestamps();

            $table->index('prefix');
            $table->index('country_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tmt_coverage');
    }
};
