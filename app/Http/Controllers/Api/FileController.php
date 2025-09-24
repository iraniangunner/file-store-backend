<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use Illuminate\Support\Facades\Storage;

class FileController extends Controller
{
    public function serve(Order $order, Request $r){
        $token = $r->query('token');
        if(!$order->isDownloadValid($token)) return response('invalid or expired token',403);
        $path = $order->product->file_path;
        if(!Storage::exists($path)) return response('file not found',404);
        return Storage::download($path, basename($path));
    }
}
