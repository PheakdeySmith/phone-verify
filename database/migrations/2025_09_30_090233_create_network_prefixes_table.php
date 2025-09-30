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
        Schema::create('network_prefixes', function (Blueprint $table) {
            $table->id();
            $table->string('prefix')->unique();
            $table->integer('min_length');
            $table->integer('max_length');
            $table->string('country_name');
            $table->string('network_name');
            $table->string('mcc')->nullable();
            $table->string('mnc')->nullable();
            $table->boolean('live_coverage')->default(false);
            $table->timestamps();
            
            // Add index for faster lookups
            $table->index('prefix');
            $table->index('country_name');
            $table->index('live_coverage');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('network_prefixes');
    }
};