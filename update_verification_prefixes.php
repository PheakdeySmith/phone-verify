<?php

require_once 'vendor/autoload.php';

use App\Models\Verification;
use App\Models\NetworkPrefix;
use App\Services\NetworkPrefixService;

// Load Laravel app
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "Starting verification prefix update...\n";

$verifications = Verification::whereNull('prefix')->get();
echo "Found " . $verifications->count() . " verifications without prefix\n";

$networkPrefixService = new NetworkPrefixService();
$updated = 0;

foreach ($verifications as $verification) {
    $phoneNumber = $verification->number;

    // Use the NetworkPrefixService to find the correct prefix
    $prefixCheck = $networkPrefixService->checkNetworkPrefix($phoneNumber);

    if ($prefixCheck['success'] && isset($prefixCheck['prefix'])) {
        $verification->update(['prefix' => $prefixCheck['prefix']]);
        $updated++;
        echo "Updated {$phoneNumber} with prefix {$prefixCheck['prefix']}\n";
    } else {
        echo "Could not find prefix for {$phoneNumber}\n";
    }
}

echo "Updated {$updated} verification records with prefix information.\n";
echo "Done!\n";