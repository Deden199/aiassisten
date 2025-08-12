<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class EnvatoService
{
    /**
     * Verify a purchase code using Envato's API.
     *
     * @param  string  $purchaseCode
     * @return array{valid: bool, data?: array, message?: string}
     */
    public function verify(string $purchaseCode): array
    {
        $token = config('license.envato_token');
        if (! $token) {
            return ['valid' => false, 'message' => 'Missing ENVATO_API_TOKEN'];
        }

        try {
            $response = Http::withToken($token)
                ->timeout(15)
                ->withUserAgent('Laravel-App-Licensing')
                ->acceptJson()
                ->get('https://api.envato.com/v3/market/author/sale', [
                    'code' => $purchaseCode,
                ]);

            if ($response->successful()) {
                return ['valid' => true, 'data' => $response->json()];
            }

            if ($response->status() === 404) {
                return ['valid' => false, 'message' => 'Invalid purchase code'];
            }

            if ($response->status() === 429) {
                return ['valid' => false, 'message' => 'Rate limited by Envato API. Try again later.'];
            }

            $message = $response->json('error') ?? ('Envato API error: '.$response->status());
            return ['valid' => false, 'message' => $message];
        } catch (\Throwable $e) {
            return ['valid' => false, 'message' => $e->getMessage()];
        }
    }
}
