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
        // Drop old table if exists
        Schema::dropIfExists('verification_results');

        // Create new verifications table with TMT Velocity API fields
        Schema::create('verifications', function (Blueprint $table) {
            $table->id();
            $table->string('number'); // The queried phone number
            $table->string('prefix')->nullable();
            $table->string('cic')->nullable(); // Carrier Identification Code
            $table->integer('error')->default(0); // Error indicator (0 = ok)
            $table->string('imsi')->nullable(); // First 5 digits of IMSI
            $table->string('mcc')->nullable(); // Mobile Country Code
            $table->string('mnc')->nullable(); // Mobile Network Code
            $table->string('network')->nullable(); // Current network name
            $table->string('ocn')->nullable(); // Operating Company Number (USA/Canada only)
            $table->boolean('ported')->default(false); // Number ported status
            $table->string('present')->nullable(); // Subscriber presence (yes/no/na)
            $table->integer('status')->default(0); // Query status (0=success, 1=invalid, 2=unauthorized, 3=congestion)
            $table->string('status_message')->nullable(); // Query status message
            $table->string('type')->nullable(); // Phone number type (mobile/fixed)
            $table->string('trxid')->nullable(); // Transaction ID for tracking
            
            
            $table->timestamps();

            $table->index('number');
            $table->index('created_at');
            $table->index('status');
            $table->index('error');
            $table->index('prefix');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('verifications');
    }
};
