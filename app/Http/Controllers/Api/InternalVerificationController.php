<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Verification;
use App\Models\NetworkPrefix;
use App\Services\TmtService;
use App\Services\OptimizedVerificationService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class InternalVerificationController extends Controller
{
    protected $tmtService;
    protected $optimizedService;

    public function __construct(TmtService $tmtService, OptimizedVerificationService $optimizedService)
    {
        $this->tmtService = $tmtService;
        $this->optimizedService = $optimizedService;
    }

      public function getAll()
    {
        $verifications = Verification::with('networkPrefix')->paginate(25);

        return response()->json([
            'success' => true,
            'data' => $verifications,
        ]);
    }

    public function show($number, Request $request)
    {
        // Clean the phone number
        $cleanNumber = preg_replace('/[^0-9]/', '', $number);
        $cacheKey = "verification_{$cleanNumber}";
        $dataFreshness = $request->get('data_freshness');

        try {
            // Handle "always get fresh data" option or any data freshness parameter
            if ($dataFreshness) {
                Log::info("Data freshness specified for number: {$cleanNumber}", ['data_freshness' => $dataFreshness]);
                // Pass dataFreshness to VerificationService to handle cache/DB bypassing
                $tmtResult = $this->tmtService->verifyNumber($cleanNumber, $dataFreshness);

                if ($tmtResult['success']) {
                    // VerificationService already saved the result, so just get it from DB
                    $verification = Verification::where('number', $cleanNumber)
                        ->with('networkPrefix')
                        ->latest()
                        ->first();

                    if ($verification) {
                        // Store in cache for future requests
                        Cache::put($cacheKey, $verification, 3600);

                        return response()->json([
                            'success' => true,
                            'data' => $verification,
                            'source' => 'tmt_api'
                        ]);
                    }
                }

                return response()->json([
                    'success' => false,
                    'message' => 'Verification failed: ' . ($tmtResult['error'] ?? 'Unknown error'),
                ], 404);
            }

            // If no data freshness specified, use the standard verification service
            // which will handle cache → database → API flow automatically
            Log::info("No data freshness specified, using standard verification flow for number: {$cleanNumber}");
            $tmtResult = $this->tmtService->verifyNumber($cleanNumber);

            if ($tmtResult['success']) {
                // VerificationService already saved the result, so just get it from DB
                $verification = Verification::where('number', $cleanNumber)
                    ->with('networkPrefix')
                    ->latest()
                    ->first();

                if ($verification) {
                    // Store in cache for future requests
                    $cacheKey = "verification_{$cleanNumber}";
                    Cache::put($cacheKey, $verification, 3600);

                    return response()->json([
                        'success' => true,
                        'data' => $verification,
                        'source' => 'tmt_api'
                    ]);
                }
            }

            return response()->json([
                'success' => false,
                'message' => 'Verification failed: ' . ($tmtResult['error'] ?? 'Unknown error'),
            ], 404);

        } catch (\Exception $e) {
            Log::error("Error in verification show: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Server error occurred',
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $data = $request->all();

            // If TMT data is provided, extract it
            if (isset($data['tmt_data'])) {
                $tmtData = $data['tmt_data'];
                $verificationData = [
                    'number' => $data['number'],
                    'country_name' => $tmtData['country_name'] ?? null,
                    'network_name' => $tmtData['network_name'] ?? null,
                    'mcc' => $tmtData['mcc'] ?? null,
                    'mnc' => $tmtData['mnc'] ?? null,
                    'status' => $tmtData['status'] ?? 0,
                    'status_text' => $tmtData['status_text'] ?? null,
                    'type' => $tmtData['type'] ?? null,
                    'ported' => $tmtData['ported'] ?? false,
                    'present' => $tmtData['present'] ?? 'na',
                    'trxid' => $tmtData['trxid'] ?? null,
                ];
            } else {
                // Direct data from request
                $verificationData = $request->only([
                    'number', 'country_name', 'network_name', 'mcc', 'mnc',
                    'status', 'status_text', 'type', 'ported', 'present', 'trxid'
                ]);
            }

            $verification = Verification::create($verificationData);

            // Load the relationship for complete data
            $verification->load('networkPrefix');

            // Store in cache
            $cacheKey = "verification_{$verification->number}";
            Cache::put($cacheKey, $verification, 3600);

            Log::info("Verification stored for number: {$verification->number}");

            return response()->json([
                'success' => true,
                'data' => $verification,
                'message' => 'Verification stored successfully'
            ], 201);

        } catch (\Exception $e) {
            Log::error("Error storing verification: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to store verification',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function update($id, Request $request)
    {
        try {
            $verification = Verification::find($id);

            if (!$verification) {
                return response()->json([
                    'success' => false,
                    'message' => 'Verification not found',
                ], 404);
            }

            $updateData = $request->only([
                'country_name', 'network_name', 'mcc', 'mnc',
                'status', 'status_text', 'type', 'ported', 'present', 'trxid'
            ]);

            $verification->update($updateData);

            // Update cache
            $cacheKey = "verification_{$verification->number}";
            Cache::put($cacheKey, $verification->fresh(), 3600);

            Log::info("Verification updated for number: {$verification->number}");

            return response()->json([
                'success' => true,
                'data' => $verification->fresh(),
                'message' => 'Verification updated successfully'
            ]);

        } catch (\Exception $e) {
            Log::error("Error updating verification: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update verification',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $verification = Verification::find($id);

            if (!$verification) {
                return response()->json([
                    'success' => false,
                    'message' => 'Verification not found',
                ], 404);
            }

            // Remove from cache
            $cacheKey = "verification_{$verification->number}";
            Cache::forget($cacheKey);

            $verification->delete();

            Log::info("Verification deleted for number: {$verification->number}");

            return response()->json([
                'success' => true,
                'message' => 'Verification deleted successfully'
            ]);

        } catch (\Exception $e) {
            Log::error("Error deleting verification: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete verification',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
