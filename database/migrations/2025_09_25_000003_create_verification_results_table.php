<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('verification_results', function (Blueprint $table) {
            $table->id();
            $table->string('phone_number');
            $table->string('current_network_name')->nullable();
            $table->string('current_network_mcc')->nullable();
            $table->string('current_network_mnc')->nullable();
            $table->string('current_network_spid')->nullable();
            $table->string('origin_network_name')->nullable();
            $table->string('origin_network_mcc')->nullable();
            $table->string('origin_network_mnc')->nullable();
            $table->string('origin_network_spid')->nullable();
            $table->integer('status')->nullable();
            $table->string('status_message')->nullable();
            $table->string('type')->nullable();
            $table->boolean('ported')->default(false);
            $table->timestamps();

            $table->index('phone_number');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('verification_results');
    }
};