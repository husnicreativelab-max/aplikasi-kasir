<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_ins', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('quantity', 12, 3);
            $table->unsignedBigInteger('purchase_price');
            $table->unsignedBigInteger('total_cost');
            $table->text('note')->nullable();
            $table->timestamp('received_at')->index();
            $table->timestamps();
            $table->index(['product_id', 'received_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_ins');
    }
};
