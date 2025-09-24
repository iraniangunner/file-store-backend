<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Carbon\Carbon;

class Order extends Model
{
    protected $fillable = [
        'user_id',
        'product_id',
        'amount',
        'currency',
        'pay_currency',
        'status',
        'provider_invoice_id',
        'provider_invoice_url',
        'provider_payload',
        'download_token',
        'download_expires_at'
    ];

    protected $casts = [
        'provider_payload' => 'array',
        'download_expires_at' => 'datetime',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // public function generateDownloadToken(int $minutes = 60*24) {
    //     $this->download_token = Str::random(48);
    //     $this->download_expires_at = Carbon::now()->addMinutes($minutes);
    //     $this->save();
    //     return $this->download_token;
    // }

    // public function isDownloadValid($token) {
    //     return $this->download_token === $token && $this->download_expires_at && $this->download_expires_at->isFuture();
    // }
    public function generateDownloadToken(int $minutes = 60): string // مدت زمان کوتاه‌تر: 1 ساعت
    {
        $this->download_token = Str::random(48);
        $this->download_expires_at = Carbon::now()->addMinutes($minutes);
        $this->save();
        return $this->download_token;
    }

    public function isDownloadValid(?string $token): bool
    {
        if (!$token || $this->download_token !== $token || !$this->download_expires_at?->isFuture()) {
            return false;
        }

        // یکبار مصرف: پس از استفاده توکن پاک شود
        $this->download_token = null;
        $this->download_expires_at = null;
        $this->save();

        return true;
    }
}
