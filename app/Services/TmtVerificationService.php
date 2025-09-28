<?php

namespace App\Services;

use Exception;
use App\Models\Verification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class TmtVerificationService
{
    private $baseUrl;
    private $apiKey;
    private $apiSecret;

    public function __construct()
    {
        $this->baseUrl = env('TMT_API_URL', 'https://api.tmtvelocity.com/live');
        $this->apiKey = env('API_KEY');
        $this->apiSecret = env('API_SECRET');
    }

    public function verifyNumber($phoneNumber, $dataFreshness = null)
    {
        // Determine if we should force fresh data
        $shouldForceFresh = $this->forceFresh($dataFreshness);

        if (!$shouldForceFresh) {
            // 1. Check Redis cache first
            $cacheKey = 'phone_verification:' . $phoneNumber;
            $cachedResult = Cache::get($cacheKey);
            if ($cachedResult) {
                // Check if cached data meets freshness requirements
                if ($this->isCacheFresh($cachedResult, $dataFreshness)) {
                    Log::info('Phone verification found in Redis cache', ['phone' => $phoneNumber]);
                    return $this->formatCached($cachedResult);
                } else {
                    // Cached data is too old, remove it
                    Cache::forget($cacheKey);
                    Log::info('Cached data too old, removed from cache', ['phone' => $phoneNumber]);
                }
            }

            // 2. Check database if not in cache
            $dbResult = Verification::where('number', $phoneNumber)->latest()->first();
            if ($dbResult && $this->isDbFresh($dbResult, $dataFreshness)) {
                Log::info('Phone verification found in database', ['phone' => $phoneNumber]);
                // Cache the database result for future requests
                Cache::put($cacheKey, $dbResult, now()->addHour());
                return $this->formatCached($dbResult);
            } elseif ($dbResult) {
                Log::info('Database data too old, forcing fresh API call', ['phone' => $phoneNumber]);
            }
        } else {
            Log::info('Forcing fresh data from API', ['phone' => $phoneNumber, 'data_freshness' => $dataFreshness]);
        }

        // 3. If not found in cache/database or data is too old, make API call
        return $this->makeApiCall($phoneNumber);
    }

    public function verifyBatch(array $phoneNumbers, $dataFreshness = null)
    {
        $results = [];
        $cacheHits = 0;
        $dbHits = 0;
        $apiCalls = 0;

        // Determine if we should force fresh data
        $shouldForceFresh = $this->forceFresh($dataFreshness);

        foreach ($phoneNumbers as $phoneNumber) {
            if (!$shouldForceFresh) {
                // Check cache first
                $cacheKey = 'phone_verification:' . $phoneNumber;
                $cachedResult = Cache::get($cacheKey);

                if ($cachedResult && $this->isCacheFresh($cachedResult, $dataFreshness)) {
                    $cacheHits++;
                    $result = $this->formatCached($cachedResult);
                    $result['source'] = 'cache';
                    $results[] = $result;
                    usleep(100000);
                    continue;
                } elseif ($cachedResult) {
                    // Cached data is too old, remove it
                    Cache::forget($cacheKey);
                }

                // Check database
                $dbResult = Verification::where('number', $phoneNumber)->latest()->first();
                if ($dbResult && $this->isDbFresh($dbResult, $dataFreshness)) {
                    $dbHits++;
                    Cache::put($cacheKey, $dbResult, now()->addHour());
                    $result = $this->formatCached($dbResult);
                    $result['source'] = 'database';
                    $results[] = $result;
                    usleep(100000);
                    continue;
                }
            }

            // Make API call if no fresh cached/database data or forcing fresh
            $apiCalls++;
            $result = $this->makeApiCall($phoneNumber);
            $result['source'] = 'api';
            $results[] = $result;
            usleep(100000);
        }

        return [
            'results' => $results,
            'statistics' => [
                'total_numbers' => count($phoneNumbers),
                'cache_hits' => $cacheHits,
                'database_hits' => $dbHits,
                'api_calls' => $apiCalls
            ]
        ];
    }

    private function formatResponse($data)
    {
        $phoneNumber = is_array($data) ? (array_keys($data)[0] ?? null) : null;
        $responseData = $phoneNumber ? $data[$phoneNumber] : [];

        return [
            'success' => ($responseData['status'] ?? 1) === 0,
            'phone_number' => $responseData['number'] ?? $phoneNumber,
            'cic' => $responseData['cic'] ?? null,
            'error' => $responseData['error'] ?? 0,
            'imsi' => $responseData['imsi'] ?? null,
            'mcc' => $responseData['mcc'] ?? null,
            'mnc' => $responseData['mnc'] ?? null,
            'network' => $responseData['network'] ?? null,
            'number' => $responseData['number'] ?? null,
            'ported' => $responseData['ported'] ?? false,
            'present' => $responseData['present'] ?? null,
            'status' => $responseData['status'] ?? 0,
            'status_message' => $responseData['status_message'] ?? 'Unknown',
            'type' => $responseData['type'] ?? null,
            'trxid' => $responseData['trxid'] ?? null,
        ];
    }

    private function formatCached($result)
    {
        // Handle both Verification model and cached array
        if ($result instanceof Verification) {
            return [
                'success' => true,
                'phone_number' => $result->number,
                'cic' => $result->cic,
                'error' => $result->error,
                'imsi' => $result->imsi,
                'mcc' => $result->mcc,
                'mnc' => $result->mnc,
                'network' => $result->network,
                'number' => $result->number,
                'ported' => $result->ported,
                'present' => $result->present,
                'status' => $result->status,
                'status_message' => $result->status_message,
                'type' => $result->type,
                'trxid' => $result->trxid,
                'cached' => true
            ];
        }

        // Handle cached array data
        return array_merge($result, ['cached' => true]);
    }

    /**
 * Executes the API call to the TMT service.
 *
 * @param string $phoneNumber The phone number to verify.
 * @return array The verification result.
 */
private function makeApiCall($phoneNumber)
{
    // 1. Validate credentials first
    if (empty($this->apiKey) || empty($this->apiSecret)) {
        return $this->handleApiError('TMT API credentials not configured', $phoneNumber, 'Configuration Error');
    }

    try {
        // 2. Make the API call
        $url = "{$this->baseUrl}/format/{$this->apiKey}/{$this->apiSecret}/{$phoneNumber}";
        Log::info('TMT API Request - cache miss', ['phone' => $phoneNumber]);
        $response = Http::get($url);

        // 3. Handle unsuccessful HTTP responses (e.g., 404, 500)
        if (!$response->successful()) {
            return $this->handleApiError(
                'API request failed with status: ' . $response->status(),
                $phoneNumber,
                'API Error',
                ['status' => $response->status(), 'response' => $response->body()]
            );
        }

        $data = $response->json();

        // 4. Handle cases where the API returns an empty but successful response
        if (empty($data)) {
            return $this->handleApiError(
                'API returned an empty response. Please check API credentials.',
                $phoneNumber,
                'Empty API Response'
            );
        }

        // 5. Format the data and persist it if the verification was successful
        $result = $this->formatResponse($data);

        if ($result['success']) {
            $this->saveResult($result);
        }

        return $result;

    } catch (Exception $e) {
        // 6. Catch any other exceptions (e.g., network connection issues)
        return $this->handleApiError($e->getMessage(), $phoneNumber, 'Exception Error');
    }
}

/**
 * Saves a successful verification result to the database and caches it.
 *
 * @param array $resultData The formatted verification data.
 * @return void
 */
private function saveResult(array $resultData)
{
    try {
        // Use updateOrCreate to find a record by 'number' and update it,
        // or create a new one if it doesn't exist. This prevents duplicates.
        $verification = Verification::updateOrCreate(
            ['number' => $resultData['number']], // <-- Attribute to find the record by
            $resultData                          // <-- Values to update or create with
        );

        // Cache the newly updated or created model instance
        $cacheKey = 'phone_verification:' . $resultData['number'];
        Cache::put($cacheKey, $verification, now()->addHour());
        Log::info('Verification result saved and cached.', ['phone' => $resultData['number']]);

    } catch (Exception $e) {
        Log::error('Failed to save or cache verification result.', [
            'phone' => $resultData['number'],
            'error' => $e->getMessage()
        ]);
        // This failure is logged but won't stop the successful API result from being returned.
    }
}

/**
 * Standardizes API error logging and response format.
 *
 * @param string $errorMessage The primary error message for the user.
 * @param string $phoneNumber The phone number related to the error.
 * @param string $statusMessage A short status code for the error type.
 * @param array $logContext Additional context for server logs.
 * @param int $statusCode The internal status code.
 * @return array The formatted error response.
 */
private function handleApiError(string $errorMessage, string $phoneNumber, string $statusMessage, array $logContext = [], int $statusCode = 1)
{
    Log::error($errorMessage, array_merge(['phone' => $phoneNumber], $logContext));

    return [
        'success' => false,
        'error' => $errorMessage,
        'phone_number' => $phoneNumber,
        'status' => $statusCode,
        'status_message' => $statusMessage,
    ];
}

    private function forceFresh($dataFreshness)
    {
        return $dataFreshness === 'all';
    }

    private function isCacheFresh($cachedResult, $dataFreshness)
    {
        if (!$dataFreshness || $dataFreshness === '') {
            return true; // Use cached data if available
        }

        if ($dataFreshness === 'all') {
            return false; // Force fresh data
        }

        // Get the timestamp of cached data
        $cachedTimestamp = null;
        if ($cachedResult instanceof Verification) {
            $cachedTimestamp = $cachedResult->created_at;
        } elseif (is_array($cachedResult) && isset($cachedResult['created_at'])) {
            $cachedTimestamp = $cachedResult['created_at'];
        }

        if (!$cachedTimestamp) {
            return false; // No timestamp, force fresh
        }

        return $this->isWithinLimit($cachedTimestamp, $dataFreshness);
    }

    private function isDbFresh($dbResult, $dataFreshness)
    {
        if (!$dataFreshness || $dataFreshness === '') {
            return true; // Use database data if available
        }

        if ($dataFreshness === 'all') {
            return false; // Force fresh data
        }

        return $this->isWithinLimit($dbResult->created_at, $dataFreshness);
    }

    private function isWithinLimit($timestamp, $dataFreshness)
    {
        $days = (int) $dataFreshness;
        if ($days <= 0) {
            return true;
        }

        $cutoffDate = now()->subDays($days);
        $dataDate = is_string($timestamp) ? \Carbon\Carbon::parse($timestamp) : $timestamp;

        return $dataDate->isAfter($cutoffDate);
    }
}