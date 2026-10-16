<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->string('number', 40)->unique();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedBigInteger('subtotal');
            $table->string('discount_type', 20)->default('nominal');
            $table->decimal('discount_value', 12, 2)->default(0);
            $table->unsignedBigInteger('discount_total')->default(0);
            $table->unsignedBigInteger('total');
            $table->string('payment_method', 20)->index();
            $table->unsignedBigInteger('amount_paid')->default(0);
            $table->unsignedBigInteger('change_due')->default(0);
            $table->string('status', 20)->default('completed')->index();
            $table->text('notes')->nullable();
            $table->timestamp('sold_at')->index();
            $table->timestamps();
            $table->index(['sold_at', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales');
    }
};
