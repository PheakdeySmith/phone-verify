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

        // Create new verifications table with TMT Velocity API fields + additional fraud check fields
        Schema::create('verifications', function (Blueprint $table) {
            $table->id();
            $table->string('number'); // The queried phone number
            $table->string('local_format')->nullable(); // Local format
            $table->boolean('valid')->nullable(); // Number validity
            $table->string('present')->nullable(); // Subscriber presence (yes/no/na)
            $table->integer('fraud_score')->nullable(); // Fraud score (0-100)
            $table->boolean('recent_abuse')->nullable(); // Recent abuse flag
            $table->boolean('voip')->nullable(); // VOIP status
            $table->boolean('prepaid')->nullable(); // Prepaid status
            $table->boolean('risky')->nullable(); // Risk flag
            $table->string('network')->nullable(); // Current network name
            $table->string('type')->nullable(); // Phone number type (mobile/fixed)
            $table->string('prefix')->nullable();
            $table->boolean('leaked_online')->nullable(); // Data leak flag
            $table->boolean('spammer')->nullable(); // Spammer flag
            $table->string('country')->nullable(); // Country code
            $table->string('city')->nullable(); // City
            $table->string('region')->nullable(); // Region/State
            $table->string('zip_code')->nullable(); // Zip/Postal code
            $table->string('timezone')->nullable(); // Timezone
            $table->string('dialing_code')->nullable(); // Country dialing code
            $table->string('cic')->nullable(); // Carrier Identification Code
            $table->integer('error')->default(0); // Error indicator (0 = ok)
            $table->string('imsi')->nullable(); // First 5 digits of IMSI
            $table->string('mcc')->nullable(); // Mobile Country Code
            $table->string('mnc')->nullable(); // Mobile Network Code
            $table->string('ocn')->nullable(); // Operating Company Number (USA/Canada only)
            $table->boolean('ported')->default(false); // Number ported status
            $table->integer('status')->default(0); // Query status (0=success, 1=invalid, 2=unauthorized, 3=congestion)
            $table->string('status_message')->nullable(); // Query status message
            $table->string('trxid')->nullable(); // Transaction ID for tracking
            
            $table->timestamps();

            $table->index('number');
            $table->index('created_at');
            $table->index('status');
            $table->index('error');
            $table->index('prefix');
            $table->index('fraud_score');
            $table->index('risky');
            $table->index('valid');
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