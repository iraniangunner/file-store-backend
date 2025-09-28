<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\Product;
use App\Services\NowPaymentsService;
use Illuminate\Support\Facades\Log;

class OrderController extends Controller
{
    public function store(Request $r, NowPaymentsService $now)
    {
        $r->validate([
            'product_id' => 'required|exists:products,id',
            'pay_currency' => 'required|string|max:10',
        ]);

        $user = $r->user();
        $product = Product::findOrFail($r->product_id);

        // ارز درخواستی
        $payCurrency = strtolower($r->pay_currency);

        // بررسی اینکه ارز پشتیبانی میشه یا نه
        $available = $now->getAvailableCurrencies();
        if (!in_array($payCurrency, $available)) {
            return response()->json([
                'message' => "ارز {$payCurrency} در حال حاضر توسط NowPayments پشتیبانی نمی‌شود."
            ], 400);
        }

        // ایجاد سفارش
        $order = Order::create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'amount' => $product->price,
            'currency' => 'usd',
            'pay_currency' => $payCurrency,
            'status' => 'waiting',
        ]);

        // payload برای NowPayments
        $payload = [
            'price_amount' => $order->amount,
            'price_currency' => $order->currency,
            'pay_currency' => $order->pay_currency,
            'order_id' => (string) $order->id,
            'ipn_callback_url' => route('webhook.nowpayments'),
            'success_url' => config('app.url') . "/payment-success?order_id={$order->id}",
            'cancel_url' => config('app.url') . "/payment-cancel?order_id={$order->id}",
            'order_description' => "Purchase {$product->title}",
        ];

        try {
            $resp = $now->createInvoice($payload);

            $order->update([
                'provider_invoice_id' => $resp['id'] ?? null,
                'provider_invoice_url' => $resp['invoice_url'] ?? $resp['url'] ?? null,
                'provider_payload' => $resp,
            ]);

            return response()->json([
                'order_id' => $order->id,
                'invoice_url' => $order->provider_invoice_url,
                'order' => $order,
            ]);
        } catch (\Exception $e) {
            Log::error("NowPayments createInvoice failed", [
                'payload' => $payload,
                'exception' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'مشکلی در ایجاد فاکتور رخ داد. لطفاً بعداً دوباره تلاش کنید.'
            ], 500);
        }
    }

    public function show(Order $order, Request $r)
    {
        if ($r->user()->id !== $order->user_id) {
            return response()->json(['message' => 'forbidden'], 403);
        }
        return response()->json($order->load('product'));
    }

    public function index(Request $r)
    {
        $user = $r->user();
    
        // همه سفارشات کاربر همراه با محصول
        $orders = Order::with('product')
            ->where('user_id', $user->id)
            ->latest()
            ->get();
    
        // اضافه کردن لینک دانلود اگر سفارش پرداخت شده باشه و توکن معتبر داشته باشه
        $orders = $orders->map(function ($order) {
            $downloadUrl = null;
    
            if (in_array($order->status, ['finished', 'confirmed']) 
                && $order->download_token 
                && $order->download_expires_at 
                && $order->download_expires_at->isFuture()
            ) {
                $downloadUrl = route('orders.file.download', [
                    'order' => $order->id,
                    'token' => $order->download_token
                ]);
            }
    
            return [
                'id' => $order->id,
                'amount' => $order->amount,
                'currency' => $order->currency,
                'pay_currency' => $order->pay_currency,
                'status' => $order->status,
                'created_at' => $order->created_at,
                'product' => $order->product,
                'download_url' => $downloadUrl, // یا null اگر منقضی یا پرداخت نشده
            ];
        });
    
        return response()->json($orders);
    }

    public function download(Order $order, Request $r)
    {
        if ($r->user()->id !== $order->user_id) {
            return response()->json(['message' => 'forbidden'], 403);
        }
        if (!in_array($order->status, ['finished', 'confirmed'])) {
            return response()->json(['message' => 'order not paid'], 403);
        }

        if (
            !$order->download_token ||
            !$order->download_expires_at ||
            $order->download_expires_at->isPast()
        ) {
            $order->generateDownloadToken(60 * 24);
        }

        $downloadLink = route('orders.file.download', [
            'order' => $order->id,
            'token' => $order->download_token,
        ]);

        return response()->json(['download_url' => $downloadLink]);
    }
}
