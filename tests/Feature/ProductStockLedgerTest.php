<?php

namespace Tests\Feature;

use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductStockLedgerTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_creating_product_with_opening_stock_writes_stock_movement(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
            'must_change_password' => false,
        ]);

        $response = $this->actingAs($admin)->post(route('products.store'), [
            'name' => 'SSD Baru',
            'barcode' => 'TEST-SSD-001',
            'category_id' => '',
            'type' => 'barang',
            'purchase_price' => 200000,
            'selling_price' => 250000,
            'stock' => 7,
            'minimum_stock' => 2,
            'unit' => 'pcs',
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('products.index'));
        $this->assertDatabaseHas('products', ['barcode' => 'TEST-SSD-001', 'stock' => 7]);
        $movement = StockMovement::query()->where('movement_type', 'opening')->firstOrFail();
        $this->assertSame('in', $movement->direction);
        $this->assertSame(7.0, (float) $movement->quantity);
        $this->assertSame(7.0, (float) $movement->stock_after);
    }
}
