<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class VerificationService
{
    private $baseUrl;
    private $token;

    public function __construct()
    {
        $this->baseUrl = config('app.url');
        $this->token = env('INTERNAL_API_TOKEN');
    }

    /**
     * Get all verifications
     */
    public function getAll()
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->token,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json'
            ])->get($this->baseUrl . '/api/verifications');

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('API Error getting all verifications', [
                'status' => $response->status(),
                'response' => $response->body()
            ]);

            return [
                'success' => false,
                'message' => 'Failed to fetch verifications',
                'status' => $response->status()
            ];

        } catch (\Exception $e) {
            Log::error('Exception in getAll verifications', [
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Network error occurred'
            ];
        }
    }

    /**
     * Get a specific verification by number
     */
    public function show($number)
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->token,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json'
            ])->get($this->baseUrl . '/api/verifications/' . $number);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('API Error getting verification', [
                'number' => $number,
                'status' => $response->status(),
                'response' => $response->body()
            ]);

            return [
                'success' => false,
                'message' => 'Failed to fetch verification',
                'status' => $response->status()
            ];

        } catch (\Exception $e) {
            Log::error('Exception in show verification', [
                'number' => $number,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Network error occurred'
            ];
        }
    }

    /**
     * Store a new verification
     */
    public function store(array $data)
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->token,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json'
            ])->post($this->baseUrl . '/api/verifications', $data);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('API Error storing verification', [
                'data' => $data,
                'status' => $response->status(),
                'response' => $response->body()
            ]);

            return [
                'success' => false,
                'message' => 'Failed to store verification',
                'status' => $response->status()
            ];

        } catch (\Exception $e) {
            Log::error('Exception in store verification', [
                'data' => $data,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Network error occurred'
            ];
        }
    }

    /**
     * Update an existing verification
     */
    public function update($id, array $data)
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->token,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json'
            ])->put($this->baseUrl . '/api/verifications/' . $id, $data);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('API Error updating verification', [
                'id' => $id,
                'data' => $data,
                'status' => $response->status(),
                'response' => $response->body()
            ]);

            return [
                'success' => false,
                'message' => 'Failed to update verification',
                'status' => $response->status()
            ];

        } catch (\Exception $e) {
            Log::error('Exception in update verification', [
                'id' => $id,
                'data' => $data,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Network error occurred'
            ];
        }
    }

    /**
     * Get JavaScript configuration for frontend
     */
    public function getJsConfig()
    {
        return [
            'baseUrl' => $this->baseUrl,
            'token' => $this->token,
            'headers' => [
                'Authorization' => 'Bearer ' . $this->token,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json'
            ]
        ];
    }
}