<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;


class NowPaymentsService
{
    protected $base;

    public function __construct()
    {
        $this->base = 'https://api.nowpayments.io/v1';
    }

    public function getAvailableCurrencies(): array
    {
        $resp = Http::withHeaders([
            'x-api-key' => config('services.nowpayments.api_key'),
            'Accept' => 'application/json',
        ])->get("{$this->base}/currencies");

        if ($resp->failed()) {
            Log::error('Failed to fetch currencies from NowPayments', [
                'response' => $resp->body()
            ]);
            return [];
        }

        return $resp->json()['currencies'] ?? [];
    }


    // ایجاد فاکتور
    public function createInvoice(array $payload)
    {
        if (app()->environment('local')) {
            return [
                'id' => uniqid(),
                'invoice_url' => 'https://sandbox.nowpayments.io/invoice/test-' . uniqid(),
                'mock' => true, // فقط برای تست مشخص بشه
                'payload' => $payload,
            ];
        }
        $resp = Http::withHeaders([
            'x-api-key' => config('services.nowpayments.api_key'),
            'Accept' => 'application/json',
        ])->post("{$this->base}/invoice", $payload);

        if ($resp->failed()) {
            Log::error('NowPayments API failed', [
                'payload' => $payload,
                'response' => $resp->body()
            ]);

            throw new \Exception('Failed to create NowPayments invoice');
        }

        return $resp->json();
    }

    // بررسی صحت امضا IPN
    public function verifyIpnSignature(string $rawBody, string $signature): bool
    {
        $data = json_decode($rawBody, true);
        if (!is_array($data)) return false;

        ksort($data);
        $sortedJson = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $expected = hash_hmac('sha512', $sortedJson, config('services.nowpayments.ipn_secret'));

        return hash_equals($expected, $signature);
    }
}
