<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('item_type', 20)->index();
            $table->string('description', 180);
            $table->string('unit', 30)->default('pcs');
            $table->decimal('quantity', 12, 3);
            $table->unsignedBigInteger('unit_price');
            $table->unsignedBigInteger('purchase_price')->default(0);
            $table->unsignedBigInteger('line_total');
            $table->timestamps();
            $table->index(['service_id', 'item_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_items');
    }
};
