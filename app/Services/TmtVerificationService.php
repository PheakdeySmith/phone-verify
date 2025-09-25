<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class TmtVerificationService
{
    private $baseUrl;
    private $apiKey;
    private $apiSecret;

    public function __construct()
    {
        $this->baseUrl = env('TMT_API_URL', 'https://api.tmtvelocity.com/standard');
        $this->apiKey = env('TMT_API_KEY');
        $this->apiSecret = env('TMT_API_SECRET');
    }

    public function verifyNumber($phoneNumber)
    {
        try {
            $url = "{$this->baseUrl}/json/{$this->apiKey}/{$this->apiSecret}/{$phoneNumber}";
            Log::info('TMT API Request', ['phone' => $phoneNumber, 'url' => $url]);
            $response = Http::get($url);

            if ($response->successful()) {
                $data = $response->json();
                Log::info('TMT API Response', ['phone' => $phoneNumber, 'response' => $data]);
                return $this->formatResponse($data);
            }

            Log::error('TMT API Error', [
                'phone' => $phoneNumber,
                'status' => $response->status(),
                'response' => $response->body()
            ]);

            return [
                'success' => false,
                'error' => 'API request failed: ' . $response->status(),
                'phone_number' => $phoneNumber,
                'status' => $response->status(),
                'status_message' => 'API Error'
            ];

        } catch (Exception $e) {
            Log::error('TMT Verification Exception', [
                'phone' => $phoneNumber,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'phone_number' => $phoneNumber,
                'status' => 1,
                'status_message' => 'Exception Error'
            ];
        }
    }

    public function verifyBatch(array $phoneNumbers)
    {
        $results = [];

        foreach ($phoneNumbers as $phoneNumber) {
            $results[] = $this->verifyNumber($phoneNumber);
            usleep(100000);
        }

        return $results;
    }

    private function formatResponse($data)
    {
        return [
            'success' => ($data['status'] ?? 1) === 0,
            'phone_number' => $data['number'] ?? null,
            'current_network' => [
                'name' => $data['current_carrier']['name'] ?? 'Unknown',
                'mcc' => $data['current_carrier']['mcc'] ?? null,
                'mnc' => $data['current_carrier']['mnc'] ?? null,
                'spid' => $data['current_carrier']['spid'] ?? null,
                'lrn' => $data['current_carrier']['lrn'] ?? null,
                'ocn' => $data['current_carrier']['ocn'] ?? null,
            ],
            'origin_network' => [
                'name' => $data['original_carrier']['name'] ?? 'Unknown',
                'mcc' => $data['original_carrier']['mcc'] ?? null,
                'mnc' => $data['original_carrier']['mnc'] ?? null,
                'spid' => $data['original_carrier']['spid'] ?? null,
                'ocn' => $data['original_carrier']['ocn'] ?? null,
            ],
            'ported' => $data['ported'] ?? false,
            'status' => $data['status'] ?? 1,
            'status_message' => $data['status_message'] ?? 'Unknown',
            'type' => $data['type'] ?? null,
        ];
    }
}