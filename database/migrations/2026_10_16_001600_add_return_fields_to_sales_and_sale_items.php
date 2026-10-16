<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->timestamp('cancelled_at')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('cancellation_reason')->nullable();
        });

        Schema::table('sale_items', function (Blueprint $table) {
            $table->string('product_type', 20)->nullable();
        });

        DB::table('sale_items')
            ->whereNotNull('product_id')
            ->update(['product_type' => DB::raw('(SELECT type FROM products WHERE products.id = sale_items.product_id)')]);

        DB::table('sale_items')->whereNull('product_type')->update(['product_type' => 'barang']);
    }

    public function down(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            $table->dropColumn('product_type');
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->dropConstrainedForeignId('cancelled_by');
            $table->dropColumn(['cancelled_at', 'cancellation_reason']);
        });
    }
};
