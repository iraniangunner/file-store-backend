<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 10, 2);
            $table->string('currency', 10)->default('usd');
            $table->string('pay_currency', 10)->default('usdt');
            $table->string('status')->default('waiting');
            $table->string('provider_invoice_id')->nullable();
            $table->string('provider_invoice_url')->nullable();
            $table->json('provider_payload')->nullable();
            $table->string('download_token')->nullable();
            $table->timestamp('download_expires_at')->nullable();
            $table->timestamps();
        });
    }

    public function down() {
        Schema::dropIfExists('orders');
    }
};
