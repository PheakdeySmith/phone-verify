<?php

namespace App\Http\Controllers;

use App\Models\VerificationResult;
use App\Services\TmtVerificationService;
use App\Exports\VerificationResultsExport;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

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
            'phone_number' => 'required|string'
        ]);

        $result = $this->verificationService->verifyNumber($request->phone_number);

        if ($result['success']) {
            $verificationResult = VerificationResult::create([
                'phone_number' => $result['phone_number'],
                'current_network_name' => $result['current_network']['name'],
                'current_network_mcc' => $result['current_network']['mcc'],
                'current_network_mnc' => $result['current_network']['mnc'],
                'current_network_spid' => $result['current_network']['spid'],
                'origin_network_name' => $result['origin_network']['name'],
                'origin_network_mcc' => $result['origin_network']['mcc'],
                'origin_network_mnc' => $result['origin_network']['mnc'],
                'origin_network_spid' => $result['origin_network']['spid'],
                'status' => $result['status'],
                'status_message' => $result['status_message'],
                'type' => $result['type'],
                'ported' => $result['ported'],

            ]);

            return response()->json([
                'success' => true,
                'data' => $verificationResult
            ]);
        }

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

        $results = $this->verificationService->verifyBatch($request->phone_numbers);
        $savedResults = [];

        foreach ($results as $result) {
            if ($result['success']) {
                $savedResults[] = VerificationResult::create([
                    'phone_number' => $result['phone_number'],
                    'current_network_name' => $result['current_network']['name'],
                    'current_network_mcc' => $result['current_network']['mcc'],
                    'current_network_mnc' => $result['current_network']['mnc'],
                    'current_network_spid' => $result['current_network']['spid'],
                    'origin_network_name' => $result['origin_network']['name'],
                    'origin_network_mcc' => $result['origin_network']['mcc'],
                    'origin_network_mnc' => $result['origin_network']['mnc'],
                    'origin_network_spid' => $result['origin_network']['spid'],
                    'status' => $result['status'],
                    'status_message' => $result['status_message'],
                    'type' => $result['type'],
                    'ported' => $result['ported']
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'processed' => count($results),
            'saved' => count($savedResults),
            'data' => $savedResults
        ]);
    }

    public function export()
    {
        return Excel::download(new VerificationResultsExport, 'verification-results.xlsx');
    }

    public function index()
    {
        $verificationResults = VerificationResult::latest()->get();
        return view('forms.verify', compact('verificationResults'));
    }

    public function results()
    {
        $results = VerificationResult::latest()->paginate(50);
        return response()->json($results);
    }
}