<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Verification;
use App\Models\NetworkPrefix;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Cache;
use App\Services\TmtService;
use App\Exports\VerificationExport;

class VerificationController extends Controller
{
    private $tmtService;

    public function __construct(TmtService $tmtService)
    {
        $this->tmtService = $tmtService;
    }

    /**
     * Display the network prefix verification page
     */
    public function index()
    {
        // Get all network prefixes for lookup
        $allNetworkPrefixes = NetworkPrefix::all()->keyBy('prefix');

        // Only show verifications that have live coverage or successful API results
        $verifications = Verification::with('networkPrefix')
            ->whereHas('networkPrefix', function($query) {
                $query->where('live_coverage', true);
            })
            ->orWhere('status', 0) // Include successful verifications even if no network prefix relation
            ->latest()
            ->get();

        // For verifications without networkPrefix relationship, try to find matching prefix
        $verifications->each(function($verification) use ($allNetworkPrefixes) {
            if (!$verification->networkPrefix) {
                $phoneNumber = $verification->number;
                // Try to find matching prefix by phone number
                for ($i = 5; $i >= 3; $i--) {
                    $prefix = substr($phoneNumber, 0, $i);
                    if ($allNetworkPrefixes->has($prefix)) {
                        $verification->setRelation('networkPrefix', $allNetworkPrefixes[$prefix]);
                        break;
                    }
                }
            }
        });

        $networkPrefixes = NetworkPrefix::where('live_coverage', true)->latest()->get();

        return view('verifications.index', compact('verifications', 'networkPrefixes'));
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
        $result = $this->tmtService->checkNetworkPrefix($phoneNumber);

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

        \Log::info('NetworkPrefixVerificationController@verify called', [
            'phone' => $phoneNumber,
            'data_freshness' => $dataFreshness
        ]);

        $result = $this->tmtService->verifyNumber($phoneNumber, $dataFreshness);

        \Log::info('VerificationService result', [
            'phone' => $phoneNumber,
            'result' => $result
        ]);

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

            $response = [
                'success' => true,
                'data' => $result
            ];

            \Log::info('Returning success response', [
                'phone' => $phoneNumber,
                'response' => $response
            ]);

            return response()->json($response);
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

        $batchResult = $this->tmtService->verifyBatchOptimized($phoneNumbers, $dataFreshness);
        $results = $batchResult['results'];
        $statistics = $batchResult['statistics'];

        // Add missing statistics for compatibility with existing code
        $statistics['total_numbers'] = $statistics['total'] ?? count($phoneNumbers);
        $statistics['database_hits'] = $statistics['db_hits'] ?? 0;

        // Calculate skipped_no_coverage from results
        $skippedNoCoverage = 0;
        foreach ($results as $result) {
            if (isset($result['skip_reason']) && $result['skip_reason'] === 'no_live_coverage') {
                $skippedNoCoverage++;
            }
        }
        $statistics['skipped_no_coverage'] = $skippedNoCoverage;

        // Separate results by type
        $savedResults = [];
        $liveCoverageResults = [];
        $noCoverageResults = [];
        $errorResults = [];

        foreach ($results as $result) {
            if ($result['success']) {
                $savedResults[] = $result;
                $liveCoverageResults[] = $result;
            } elseif (isset($result['skip_reason']) && $result['skip_reason'] === 'no_live_coverage') {
                $savedResults[] = $result;
                $noCoverageResults[] = $result;
            } else {
                $errorResults[] = $result;
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
            'live_coverage_count' => count($liveCoverageResults),
            'no_coverage_count' => count($noCoverageResults),
            'error_count' => count($errorResults),
            'skipped_no_coverage' => $statistics['skipped_no_coverage'],
            'statistics' => [
                'cache_hits' => $statistics['cache_hits'],
                'database_hits' => $statistics['database_hits'],
                'api_calls' => $statistics['api_calls'],
                'skipped_no_coverage' => $statistics['skipped_no_coverage'],
                'total_cached' => $statistics['cache_hits'] + $statistics['database_hits'],
                'live_coverage_results' => count($liveCoverageResults),
                'no_coverage_results' => count($noCoverageResults),
                'error_results' => count($errorResults)
            ],
            'cache_message' => $this->buildCacheMsg($statistics),
            'data' => $savedResults,
            'live_coverage_data' => $liveCoverageResults,
            'no_coverage_data' => $noCoverageResults,
            'error_data' => $errorResults
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