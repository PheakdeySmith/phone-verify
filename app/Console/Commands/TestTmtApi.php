<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\PhoneVerificationService;

class TestTmtApi extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tmt:test {phone_number : The phone number to test}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test TMT API integration with a phone number';

    protected $verificationService;

    public function __construct(PhoneVerificationService $verificationService)
    {
        parent::__construct();
        $this->verificationService = $verificationService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $phoneNumber = $this->argument('phone_number');

        $this->info("Testing TMT API with phone number: {$phoneNumber}");
        $this->line("===========================================");

        // Test basic query first
        $this->info('1. Testing Basic Query...');
        $basicResult = $this->verificationService->basicQuery($phoneNumber);

        if ($basicResult['success']) {
            $this->info('✅ Basic Query successful');
            $this->table(
                ['Field', 'Value'],
                [
                    ['Supported', $basicResult['supported'] ? 'Yes' : 'No'],
                    ['Recommended Provider', $basicResult['recommended_provider'] ?? 'N/A'],
                    ['Estimated Cost', '$' . ($basicResult['estimated_cost'] ?? '0')],
                ]
            );
        } else {
            $this->error('❌ Basic Query failed: ' . $basicResult['message']);
            return Command::FAILURE;
        }

        // Test advanced verification
        $this->info('2. Testing Advanced Verification...');
        $advancedResult = $this->verificationService->advancedVerify($phoneNumber, true); // Force fresh

        if ($advancedResult['success']) {
            $this->info('✅ Advanced Verification successful');

            $verification = $advancedResult['verification'];
            $this->table(
                ['Field', 'Value'],
                [
                    ['ID', $verification->id],
                    ['Phone Number', $verification->phone_number],
                    ['Provider', $verification->provider],
                    ['Cost', '$' . $verification->cost],
                    ['Status', $verification->tmt_status],
                    ['Present', $verification->tmt_present],
                    ['Network', $verification->tmt_network],
                    ['Country', $verification->tmt_country],
                    ['Ported', $verification->tmt_ported ? 'Yes' : 'No'],
                    ['Created At', $verification->created_at->format('Y-m-d H:i:s')],
                ]
            );
        } else {
            $this->error('❌ Advanced Verification failed: ' . $advancedResult['message']);
            return Command::FAILURE;
        }

        $this->info('🎉 All tests completed successfully!');
        return Command::SUCCESS;
    }
}