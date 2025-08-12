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
        try {
            $response = Http::withToken(config('services.envato.token'))
                ->acceptJson()
                ->get('https://api.envato.com/v3/market/author/sale', [
                    'code' => $purchaseCode,
                ]);

            if ($response->successful()) {
                return ['valid' => true, 'data' => $response->json()];
            }

            $message = $response->json('error') ?? 'Verification failed';
            return ['valid' => false, 'message' => $message];
        } catch (\Throwable $e) {
            return ['valid' => false, 'message' => $e->getMessage()];
        }
    }
}

