<?php

namespace App\Http\Controllers;

use App\Services\PhoneVerificationService;
use App\Models\Verification;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class PhoneVerificationController extends Controller
{
    protected $verificationService;

    public function __construct(PhoneVerificationService $verificationService)
    {
        $this->verificationService = $verificationService;
    }

    /**
     * Display the verification page
     */
    public function index()
    {
        $verifications = Verification::latest()->get();
        return view('verifications.index', compact('verifications'));
    }

    /**
     * BASIC QUERY ENDPOINT - FREE
     * Just checks coverage tables, no API call, no database storage
     */
    public function basicQuery(Request $request)
    {
        $request->validate([
            'phone_number' => 'required|string'
        ]);

        $result = $this->verificationService->basicQuery($request->phone_number);

        return response()->json($result);
    }

    /**
     * ADVANCED VERIFICATION ENDPOINT - PAID
     * Full verification with caching and API call
     */
    public function advancedVerify(Request $request)
    {
        $request->validate([
            'phone_number' => 'required|string',
            'force_reverify' => 'sometimes|boolean',
            'data_freshness' => 'nullable|string'
        ]);

        $forceReverify = $request->input('force_reverify', false);
        $dataFreshness = $request->input('data_freshness');

        // Determine if we should force re-verification based on data freshness
        if ($dataFreshness === 'all') {
            $forceReverify = true;
        } elseif (in_array($dataFreshness, ['30', '60', '90'])) {
            // Check if existing data is older than specified days
            $existingVerification = Verification::where('phone_number', $request->phone_number)->first();

            if ($existingVerification) {
                $daysOld = now()->diffInDays($existingVerification->updated_at);
                $maxAgeDays = (int)$dataFreshness;

                if ($daysOld >= $maxAgeDays) {
                    $forceReverify = true;
                }
            }
        }

        $result = $this->verificationService->advancedVerify($request->phone_number, $forceReverify);

        return response()->json($result);
    }

    public function getVerification($id)
    {
        $verification = Verification::findOrFail($id);

        return response()->json([
            'verification' => $verification,
            'provider_data' => $verification->getProviderData()
        ]);
    }

    public function history(Request $request)
    {
        $query = Verification::latest();

        if ($request->has('provider')) {
            $query->where('provider', $request->provider);
        }

        if ($request->has('phone_number')) {
            $query->where('phone_number', 'like', '%' . $request->phone_number . '%');
        }

        $verifications = $query->paginate(20);

        return response()->json($verifications);
    }

    public function statistics()
    {
        $stats = [
            'total_verifications' => Verification::count(),
            'unique_numbers' => Verification::distinct('phone_number')->count(),
            'by_provider' => [
                'TMT' => Verification::tmt()->count(),
                'IPQS' => Verification::ipqs()->count(),
            ],
            'total_cost' => Verification::sum('cost'),
            'average_cost' => Verification::avg('cost'),
            'cost_by_provider' => [
                'TMT' => Verification::tmt()->sum('cost'),
                'IPQS' => Verification::ipqs()->sum('cost'),
            ]
        ];

        return response()->json($stats);
    }

    /**
     * Check network prefix for a phone number (for real-time validation)
     */
    public function checkNetworkPrefix(Request $request)
    {
        $request->validate([
            'phone_number' => 'required|string',
        ]);

        $phoneNumber = $request->phone_number;
        $result = $this->verificationService->checkNetworkPrefix($phoneNumber);

        return response()->json($result);
    }

    /**
     * LEGACY METHODS - For backward compatibility
     */
    public function verify(Request $request)
    {
        // Redirect to advanced verification
        return $this->advancedVerify($request);
    }

    public function previewBatchCost(Request $request)
    {
        try {
            $request->validate([
                'phone_numbers' => 'required|array',
                'phone_numbers.*' => 'string'
            ]);

            $phoneNumbers = $request->phone_numbers;
            $result = $this->verificationService->calculateBatchCost($phoneNumbers);

            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function verifyBatch(Request $request)
    {
        try {
            \Log::info('Batch verification started', [
                'phone_numbers_count' => is_array($request->phone_numbers) ? count($request->phone_numbers) : 0,
                'data_freshness' => $request->data_freshness
            ]);

            $request->validate([
                'phone_numbers' => 'required|array',
                'phone_numbers.*' => 'string',
                'data_freshness' => 'nullable|string'
            ]);

            $phoneNumbers = $request->phone_numbers;
            $dataFreshness = $request->data_freshness;

            $result = $this->verificationService->verifyBatch($phoneNumbers, $dataFreshness);

            \Log::info('Batch verification completed', ['result_count' => count($result['data'] ?? [])]);

            return response()->json($result);
        } catch (\Exception $e) {
            \Log::error('Batch verification error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function export()
    {
        return Excel::download(new \App\Exports\VerificationExport, 'verifications_' . date('Y-m-d_H-i-s') . '.xlsx');
    }
}