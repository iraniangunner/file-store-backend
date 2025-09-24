<?php
namespace App\Http\Controllers\Webhook;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\NowPaymentsService;
use App\Models\Order;
use Illuminate\Support\Facades\Log;


class NowPaymentsWebhookController extends Controller
{
    public function __invoke(Request $request, NowPaymentsService $now)
    {
        $raw = $request->getContent(); // file_get_contents('php://input') هم می‌شه
        $sig = $request->header('x-nowpayments-sig');

        

        // فقط در محیط غیر لوکال بررسی امضا
        if (!app()->environment('local') && (!$sig || !$now->verifyIpnSignature($raw, $sig))) {
            return response('invalid signature', 403);
        }

        $data = json_decode($raw, true);

        Log::info('Received NowPayments Webhook', [
            'payload' => $data,
            'signature' => $sig
        ]);
        
        $orderId = $data['order_id'] ?? null;
        if (!$orderId) return response('no order_id', 400);

        $order = Order::find($orderId);
        if (!$order) return response('order not found', 404);

        $status = $data['payment_status'] ?? $data['status'] ?? null;

        // اگر سفارش قبلاً پرداخت شده باشد و وضعیت جدید هم پرداخت شده است
        if (in_array($order->status, ['finished', 'confirmed']) && in_array($status, ['finished', 'confirmed'])) {
            return response('ok', 200);
        }

        // آپدیت وضعیت و payload
        $order->update([
            'status' => $status,
            'provider_payload' => $data
        ]);

        // اگر وضعیت پرداخت کامل شد، توکن دانلود ایجاد کن
        if (in_array($status, ['finished', 'confirmed'])) {
            $order->generateDownloadToken(60*24);
        }

        return response('ok', 200);
    }
}
