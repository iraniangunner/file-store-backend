<?php

namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Product;

class ProductController extends Controller
{

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string',
            'description' => 'nullable|string',
            'price' => 'required|numeric',
            'file' => 'required|file|max:10240', // حداکثر 10MB
        ]);

        // آپلود فایل به storage/app/products
        $filePath = $request->file('file')->store('products');

        $product = Product::create([
            'title' => $request->title,
            'description' => $request->description,
            'price' => $request->price,
            'file_path' => $filePath,
            'mime' => $request->file('file')->getClientMimeType(),
            'file_size' => $request->file('file')->getSize(),
            'is_active' => true
        ]);

        return response()->json($product, 201);
    }
    public function index()
    {
        return response()->json(Product::where('is_active', true)->get());
    }

    public function show(Product $product)
    {
        return response()->json($product);
    }
}
