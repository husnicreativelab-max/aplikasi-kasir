<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->string('number', 40)->unique();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('customer_name', 160)->index();
            $table->string('customer_phone', 30)->index();
            $table->string('device_type', 80);
            $table->string('brand_model', 160);
            $table->string('serial_number', 120)->nullable()->index();
            $table->text('complaint');
            $table->text('accessories')->nullable();
            $table->text('device_password')->nullable();
            $table->unsignedBigInteger('estimated_cost')->default(0);
            $table->unsignedBigInteger('deposit_amount')->default(0);
            $table->unsignedBigInteger('paid_total')->default(0);
            $table->string('status', 32)->default('Diterima')->index();
            $table->timestamp('received_at')->index();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('taken_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('cancellation_reason')->nullable();
            $table->timestamps();
            $table->index(['status', 'received_at']);
            $table->index(['customer_name', 'customer_phone']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('services');
    }
};
