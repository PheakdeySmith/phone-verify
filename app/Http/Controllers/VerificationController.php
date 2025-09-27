<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Verification;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Cache;
use App\Services\TmtVerificationService;
use App\Exports\VerificationExport;

class VerificationController extends Controller
{
    private $verificationService;

    public function __construct(TmtVerificationService $verificationService)
    {
        $this->verificationService = $verificationService;
    }

    public function verify(Request $request)
    {
        $request->validate([
        'phone_number' => 'required|string',
        'data_freshness' => 'nullable|string' // Good practice to validate it
    ]);

    $phoneNumber = $request->phone_number;
    // FIX: Get 'data_freshness' from the request.
    $dataFreshness = $request->data_freshness; 

    // FIX: Pass the value to the service.
    $result = $this->verificationService->verifyNumber($phoneNumber, $dataFreshness);

        // If result is cached, return it directly
        if (isset($result['cached']) && $result['cached']) {
            return response()->json([
                'success' => $result['success'],
                'data' => $result,
                'cached' => true
            ]);
        }

        if ($result['success']) {
            // Track verification stats in Cache
            $this->incrementStat('verification_stats:total');
            $this->incrementStat('verification_stats:successful');
            $this->incrementStat('verification_stats:today:' . now()->format('Y-m-d'));

            return response()->json([
                'success' => true,
                'data' => $result
            ]);
        }

        // Track failed verification
        $this->incrementStat('verification_stats:total');
        $this->incrementStat('verification_stats:failed');

        return response()->json([
            'success' => false,
            'error' => $result['error'] ?? 'Verification failed'
        ], 400);
    }

    public function verifyBatch(Request $request)
    {
        $request->validate([
            'phone_numbers' => 'required|array',
            'phone_numbers.*' => 'required|string'
        ]);

        $phoneNumbers = $request->phone_numbers;

        // Use the new batch verification service that returns statistics
        $batchResult = $this->verificationService->verifyBatch($phoneNumbers);
        $results = $batchResult['results'];
        $statistics = $batchResult['statistics'];

        // Separate successful results for saving
        $savedResults = [];
        foreach ($results as $result) {
            if ($result['success']) {
                $savedResults[] = $result;
            }
        }

        // Update batch verification stats
        $this->incrementStat('verification_stats:batch_total');
        $this->incrementStatBy('verification_stats:batch_numbers', $statistics['total_numbers']);
        $this->incrementStatBy('verification_stats:cached_hits', $statistics['cache_hits']);
        $this->incrementStatBy('verification_stats:database_hits', $statistics['database_hits']);
        $this->incrementStatBy('verification_stats:api_calls', $statistics['api_calls']);

        return response()->json([
            'success' => true,
            'processed' => $statistics['total_numbers'],
            'saved' => count($savedResults),
            'statistics' => [
                'cache_hits' => $statistics['cache_hits'],
                'database_hits' => $statistics['database_hits'],
                'api_calls' => $statistics['api_calls'],
                'total_cached' => $statistics['cache_hits'] + $statistics['database_hits']
            ],
            'cache_message' => $this->generateCacheMessage($statistics),
            'data' => $savedResults
        ]);
    }

    public function export()
    {
        return Excel::download(new VerificationExport, 'verification-results.xlsx');
    }

    public function index()
    {
        $verifications=  Verification::latest()->get();

        return view('forms.verify', compact('verifications'));
    }

    public function results()
    {
        $page = request()->get('page', 1);
        $cacheKey = 'verification_results_paginated:' . $page;

        $results = Cache::remember($cacheKey, 300, function () {
            return Verification::latest()->paginate(50);
        });

        return response()->json($results);
    }

    public function stats()
    {
        $stats = [
            'total_verifications' => $this->getStat('verification_stats:total'),
            'successful' => $this->getStat('verification_stats:successful'),
            'failed' => $this->getStat('verification_stats:failed'),
            'batch_total' => $this->getStat('verification_stats:batch_total'),
            'batch_numbers' => $this->getStat('verification_stats:batch_numbers'),
            'cached_hits' => $this->getStat('verification_stats:cached_hits'),
            'today' => $this->getStat('verification_stats:today:' . now()->format('Y-m-d')),
        ];

        return response()->json($stats);
    }

    private function incrementStat($key)
    {
        $current = Cache::get($key, 0);
        Cache::put($key, $current + 1, now()->addDays(30));
    }

    private function incrementStatBy($key, $value)
    {
        $current = Cache::get($key, 0);
        Cache::put($key, $current + $value, now()->addDays(30));
    }

    private function getStat($key)
    {
        return Cache::get($key, 0);
    }

    private function generateCacheMessage($statistics)
    {
        $total = $statistics['total_numbers'];
        $cacheHits = $statistics['cache_hits'];
        $dbHits = $statistics['database_hits'];
        $apiCalls = $statistics['api_calls'];
        $totalCached = $cacheHits + $dbHits;

        $message = "Cache Performance: ";
        $message .= "{$totalCached} numbers found in cache ({$cacheHits} from Redis cache, {$dbHits} from database), ";
        $message .= "{$apiCalls} new API calls made";

        if ($total > 0) {
            $cachePercentage = round(($totalCached / $total) * 100, 1);
            $message .= " - {$cachePercentage}% cache hit rate";
        }

        return $message;
    }
}