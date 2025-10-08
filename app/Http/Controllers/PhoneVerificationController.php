<?php

namespace App\Http\Controllers;

use App\Services\PhoneVerificationService;
use App\Models\Verification;
use Illuminate\Http\Request;

class PhoneVerificationController extends Controller
{
    protected $verificationService;

    public function __construct(PhoneVerificationService $verificationService)
    {
        $this->verificationService = $verificationService;
    }

    public function index()
    {
        // Get recent verifications to display in the table
        $verifications = Verification::latest()->take(100)->get();

        return view('verifications.index', compact('verifications'));
    }

    public function verify(Request $request)
    {
        $request->validate([
            'phone_number' => 'required|string'
        ]);

        $result = $this->verificationService->verify($request->phone_number);

        return response()->json($result);
    }

    public function checkNetworkPrefix(Request $request)
    {
        $request->validate([
            'phone_number' => 'required|string'
        ]);

        $result = $this->verificationService->checkNetworkPrefix($request->phone_number);

        return response()->json($result);
    }

    public function verifyBatch(Request $request)
    {
        $request->validate([
            'phone_numbers' => 'required|array',
            'phone_numbers.*' => 'required|string',
            'data_freshness' => 'nullable|string'
        ]);

        $result = $this->verificationService->verifyBatch(
            $request->phone_numbers,
            $request->data_freshness
        );

        return response()->json($result);
    }

    public function export()
    {
        return $this->verificationService->exportToExcel();
    }

    public function getVerification($id)
    {
        $verification = Verification::findOrFail($id);

        return response()->json([
            'verification' => $verification
        ]);
    }

    public function history(Request $request)
    {
        $query = Verification::latest();

        // Filter by provider if requested
        if ($request->has('provider')) {
            $query->where('provider', $request->provider);
        }

        // Filter by phone number if requested
        if ($request->has('phone_number')) {
            $query->where('phone_number', 'like', "%{$request->phone_number}%");
        }

        $verifications = $query->paginate(20);

        return response()->json($verifications);
    }

    // Get statistics
    public function statistics()
    {
        $stats = [
            'total_verifications' => Verification::query()->count(),
            'by_provider' => [
                'TMT' => Verification::where('provider', 'TMT')->count(),
                'IPQS' => Verification::where('provider', 'IPQS')->count(),
            ],
            'total_cost' => Verification::query()->sum('cost'),
            'average_cost' => Verification::query()->avg('cost'),
            'cost_by_provider' => [
                'TMT' => Verification::where('provider', 'TMT')->sum('cost'),
                'IPQS' => Verification::where('provider', 'IPQS')->sum('cost'),
            ]
        ];

        return response()->json($stats);
    }
}