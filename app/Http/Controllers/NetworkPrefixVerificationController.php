<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Verification;
use App\Models\NetworkPrefix;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Cache;
use App\Services\NetworkPrefixService;
use App\Exports\VerificationExport;

class NetworkPrefixVerificationController extends Controller
{
    private $networkPrefixService;

    public function __construct(NetworkPrefixService $networkPrefixService)
    {
        $this->networkPrefixService = $networkPrefixService;
    }

    /**
     * Display the network prefix verification page
     */
    public function index()
    {
        $verifications = Verification::latest()->get();
        $networkPrefixes = NetworkPrefix::latest()->get();

        return view('forms.network-verification', compact('verifications', 'networkPrefixes'));
    }

    /**
     * Check network prefix for a phone number
     */
    public function checkNetworkPrefix(Request $request)
    {
        $request->validate([
            'phone_number' => 'required|string',
        ]);

        $phoneNumber = $request->phone_number;
        $result = $this->networkPrefixService->checkNetworkPrefix($phoneNumber);

        return response()->json($result);
    }

    /**
     * Verify a single phone number using network prefix service
     */
    public function verify(Request $request)
    {
        $request->validate([
            'phone_number' => 'required|string',
            'data_freshness' => 'nullable|string',
        ]);

        $phoneNumber = $request->phone_number;
        $dataFreshness = $request->data_freshness;

        $result = $this->networkPrefixService->verifyNumber($phoneNumber, $dataFreshness);

        // If result is cached, return it directly
        if (isset($result['cached']) && $result['cached']) {
            return response()->json([
                'success' => $result['success'],
                'data' => $result,
                'cached' => true
            ]);
        }

        if ($result['success'] || $result['skip_reason'] === 'no_live_coverage') {
            // Track verification stats in Cache
            $this->incrementStat('network_verification_stats:total');

            if ($result['success']) {
                $this->incrementStat('network_verification_stats:successful');
            } else {
                $this->incrementStat('network_verification_stats:skipped_no_coverage');
            }

            $this->incrementStat('network_verification_stats:today:' . now()->format('Y-m-d'));

            return response()->json([
                'success' => true,
                'data' => $result
            ]);
        }

        // Track failed verification
        $this->incrementStat('network_verification_stats:total');
        $this->incrementStat('network_verification_stats:failed');

        return response()->json([
            'success' => false,
            'error' => $result['error'] ?? 'Verification failed'
        ], 400);
    }

    /**
     * Verify multiple phone numbers using network prefix service
     */
    public function verifyBatch(Request $request)
    {
        $request->validate([
            'phone_numbers' => 'required|array',
            'phone_numbers.*' => 'required|string',
            'data_freshness' => 'nullable|string|in:30,60,90,all'
        ]);

        $phoneNumbers = $request->phone_numbers;
        $dataFreshness = $request->data_freshness;

        $batchResult = $this->networkPrefixService->verifyBatch($phoneNumbers, $dataFreshness);
        $results = $batchResult['results'];
        $statistics = $batchResult['statistics'];

        // Separate successful results for saving
        $savedResults = [];
        foreach ($results as $result) {
            if ($result['success'] || (isset($result['skip_reason']) && $result['skip_reason'] === 'no_live_coverage')) {
                $savedResults[] = $result;
            }
        }

        // Update batch verification stats
        $this->incrementStat('network_verification_stats:batch_total');
        $this->addToStat('network_verification_stats:batch_numbers', $statistics['total_numbers']);
        $this->addToStat('network_verification_stats:cached_hits', $statistics['cache_hits']);
        $this->addToStat('network_verification_stats:database_hits', $statistics['database_hits']);
        $this->addToStat('network_verification_stats:api_calls', $statistics['api_calls']);
        $this->addToStat('network_verification_stats:skipped_no_coverage', $statistics['skipped_no_coverage']);

        return response()->json([
            'success' => true,
            'processed' => $statistics['total_numbers'],
            'saved' => count($savedResults),
            'skipped_no_coverage' => $statistics['skipped_no_coverage'],
            'statistics' => [
                'cache_hits' => $statistics['cache_hits'],
                'database_hits' => $statistics['database_hits'],
                'api_calls' => $statistics['api_calls'],
                'skipped_no_coverage' => $statistics['skipped_no_coverage'],
                'total_cached' => $statistics['cache_hits'] + $statistics['database_hits']
            ],
            'cache_message' => $this->buildCacheMsg($statistics),
            'data' => $savedResults
        ]);
    }

    /**
     * Export verification results
     */
    public function export()
    {
        return Excel::download(new VerificationExport, 'network-verification-results.xlsx');
    }

    /**
     * Get verification statistics
     */
    public function stats()
    {
        $stats = [
            'total_verifications' => $this->getStat('network_verification_stats:total'),
            'successful' => $this->getStat('network_verification_stats:successful'),
            'failed' => $this->getStat('network_verification_stats:failed'),
            'skipped_no_coverage' => $this->getStat('network_verification_stats:skipped_no_coverage'),
            'batch_total' => $this->getStat('network_verification_stats:batch_total'),
            'batch_numbers' => $this->getStat('network_verification_stats:batch_numbers'),
            'cached_hits' => $this->getStat('network_verification_stats:cached_hits'),
            'today' => $this->getStat('network_verification_stats:today:' . now()->format('Y-m-d')),
        ];

        return response()->json($stats);
    }

    private function incrementStat($key)
    {
        $current = Cache::get($key, 0);
        Cache::put($key, $current + 1, now()->addDays(30));
    }

    private function addToStat($key, $value)
    {
        $current = Cache::get($key, 0);
        Cache::put($key, $current + $value, now()->addDays(30));
    }

    private function getStat($key)
    {
        return Cache::get($key, 0);
    }

    private function buildCacheMsg($statistics)
    {
        $total = $statistics['total_numbers'];
        $cacheHits = $statistics['cache_hits'];
        $dbHits = $statistics['database_hits'];
        $apiCalls = $statistics['api_calls'];
        $skippedNoCoverage = $statistics['skipped_no_coverage'];
        $totalCached = $cacheHits + $dbHits;

        $message = "Performance: ";
        $message .= "{$totalCached} numbers found in cache ({$cacheHits} from Redis, {$dbHits} from database), ";
        $message .= "{$apiCalls} new API calls made, ";
        $message .= "{$skippedNoCoverage} skipped (no live coverage)";

        if ($total > 0) {
            $cachePercentage = round(($totalCached / $total) * 100, 1);
            $message .= " - {$cachePercentage}% cache hit rate";
        }

        return $message;
    }
}