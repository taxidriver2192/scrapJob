<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class VirkdataService
{
    protected $baseUrl = 'https://virkdata.dk/api/';
    protected $apiKey;

    public function __construct()
    {
        $this->apiKey = config('services.virkdata.key');
    }

    /**
     * Search Virkdata by company name.
     * Returns array on success or throws exception.
     */
    public function fetch(string $search): array
    {
        if (empty($this->apiKey)) {
            return ['error' => ['message' => 'Virkdata API key is not configured']];
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => $this->apiKey,
            ])->timeout(30)->get($this->baseUrl, [
                'search' => $search,
                'format' => 'json',
                'country' => 'dk',
                'financial_summary' => 'true',
            ]);

            $data = $response->json();

            if ($response->failed()) {
                Log::warning('Virkdata API request failed', [
                    'status' => $response->status(),
                    'search' => $search,
                    'response' => $data
                ]);
                return ['error' => $data ?: ['message' => 'API request failed', 'status' => $response->status()]];
            }

            if (isset($data['error_code']) || empty($data['vat'])) {
                Log::info('Virkdata API returned no results or error', [
                    'search' => $search,
                    'response' => $data
                ]);
                return ['error' => $data ?: ['message' => 'No company data found']];
            }

            Log::info('Successfully fetched Virkdata for company', [
                'search' => $search,
                'vat' => $data['vat'] ?? 'unknown'
            ]);

            return $data;

        } catch (\Exception $e) {
            Log::error('Exception while fetching Virkdata', [
                'search' => $search,
                'error' => $e->getMessage()
            ]);

            return ['error' => ['message' => $e->getMessage()]];
        }
    }
}
