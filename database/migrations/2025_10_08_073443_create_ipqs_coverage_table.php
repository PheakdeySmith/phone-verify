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
        Schema::create('ipqs_coverage', function (Blueprint $table) {
            $table->id();
            $table->string('country', 2);
            $table->string('operator_id');
            $table->string('carrier_name');
            $table->string('cc');
            $table->string('number_prefix');
            $table->boolean('support_provider')->default(true);
            $table->decimal('price', 10, 6);
            $table->timestamps();

            $table->index('number_prefix');
            $table->index('cc');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ipqs_coverage');
    }
};
