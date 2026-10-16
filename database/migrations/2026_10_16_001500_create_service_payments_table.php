<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('payment_type', 20);
            $table->string('payment_method', 20);
            $table->unsignedBigInteger('amount');
            $table->unsignedBigInteger('amount_received')->default(0);
            $table->unsignedBigInteger('change_due')->default(0);
            $table->text('note')->nullable();
            $table->timestamp('paid_at')->index();
            $table->timestamps();
            $table->index(['service_id', 'payment_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_payments');
    }
};
