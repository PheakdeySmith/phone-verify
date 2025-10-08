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
        Schema::create('verifications', function (Blueprint $table) {
            $table->id();
            $table->string('phone_number');
            $table->enum('provider', ['TMT', 'IPQS']);
            $table->decimal('cost', 10, 6)->nullable();

            // TMT response fields
            $table->string('tmt_prefix')->nullable();
            $table->string('tmt_cic')->nullable();
            $table->string('tmt_imsi')->nullable();
            $table->string('tmt_mcc')->nullable();
            $table->string('tmt_mnc')->nullable();
            $table->string('tmt_network')->nullable();
            $table->boolean('tmt_ported')->nullable();
            $table->string('tmt_present')->nullable();
            $table->integer('tmt_status')->nullable();
            $table->string('tmt_trxid')->nullable();

            // IPQS response fields
            $table->string('ipqs_formatted')->nullable();
            $table->string('ipqs_local_format')->nullable();
            $table->boolean('ipqs_valid')->nullable();
            $table->boolean('ipqs_active')->nullable();
            $table->integer('ipqs_fraud_score')->nullable();
            $table->boolean('ipqs_recent_abuse')->nullable();
            $table->boolean('ipqs_voip')->nullable();
            $table->boolean('ipqs_prepaid')->nullable();
            $table->boolean('ipqs_risky')->nullable();
            $table->string('ipqs_name')->nullable();
            $table->text('ipqs_associated_emails')->nullable();
            $table->string('ipqs_carrier')->nullable();
            $table->string('ipqs_line_type')->nullable();
            $table->boolean('ipqs_leaked_online')->nullable();
            $table->boolean('ipqs_spammer')->nullable();
            $table->string('ipqs_country')->nullable();
            $table->string('ipqs_city')->nullable();
            $table->string('ipqs_region')->nullable();
            $table->string('ipqs_zip_code')->nullable();
            $table->string('ipqs_timezone')->nullable();
            $table->string('ipqs_dialing_code')->nullable();
            $table->string('ipqs_active_status_enhanced')->nullable();
            $table->string('ipqs_request_id')->nullable();

            $table->timestamps();

            $table->index('phone_number');
            $table->index('provider');
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
