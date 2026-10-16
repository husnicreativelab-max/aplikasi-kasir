<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table): void {
            $table->index(['status', 'sold_at'], 'sales_status_sold_at_index');
        });

        Schema::table('stock_movements', function (Blueprint $table): void {
            $table->index(['created_at', 'id'], 'stock_movements_created_at_id_index');
        });
    }

    public function down(): void
    {
        Schema::table('stock_movements', function (Blueprint $table): void {
            $table->dropIndex('stock_movements_created_at_id_index');
        });

        Schema::table('sales', function (Blueprint $table): void {
            $table->dropIndex('sales_status_sold_at_index');
        });
    }
};
