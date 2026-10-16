<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Setting;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SalesCheckoutTest extends TestCase
{
    use RefreshDatabase;

    private User $cashier;
    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        Setting::putMany([
            'allow_negative_stock' => '0',
            'invoice_prefix' => 'INV',
        ]);

        $this->cashier = User::factory()->create([
            'role' => 'kasir',
            'is_active' => true,
            'must_change_password' => false,
        ]);
        $category = Category::query()->create(['name' => 'Aksesoris', 'is_active' => true]);
        $this->product = Product::query()->create([
            'category_id' => $category->id,
            'barcode' => 'TEST-001',
            'name' => 'Mouse Uji',
            'type' => 'barang',
            'purchase_price' => 30000,
            'selling_price' => 50000,
            'stock' => 5,
            'minimum_stock' => 1,
            'unit' => 'pcs',
            'is_active' => true,
        ]);
    }

    public function test_checkout_saves_sale_and_deducts_stock_with_movement_record(): void
    {
        $response = $this->actingAs($this->cashier)->postJson(route('sales.store'), [
            'items' => [['product_id' => $this->product->id, 'quantity' => 2]],
            'discount_type' => 'nominal',
            'discount_value' => 5000,
            'payment_method' => 'tunai',
            'amount_paid' => 100000,
        ]);

        $response->assertCreated();
        $this->assertMatchesRegularExpression('/^INV-\d{8}-0001$/', (string) $response->json('number'));

        $sale = Sale::query()->firstOrFail();
        $this->assertSame(95000, $sale->total);
        $this->assertSame(5000, $sale->change_due);
        $this->assertSame(3.0, (float) $this->product->fresh()->stock);
        $this->assertDatabaseHas('sale_items', [
            'sale_id' => $sale->id,
            'product_id' => $this->product->id,
            'purchase_price' => 30000,
            'unit' => 'pcs',
        ]);
        $this->assertSame(1, StockMovement::query()->where('movement_type', 'sale')->count());
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $this->product->id,
            'direction' => 'out',
            'reference_id' => $sale->id,
        ]);
    }

    public function test_checkout_rejects_insufficient_stock_without_partial_records(): void
    {
        $response = $this->actingAs($this->cashier)->postJson(route('sales.store'), [
            'items' => [['product_id' => $this->product->id, 'quantity' => 6]],
            'discount_type' => 'none',
            'discount_value' => 0,
            'payment_method' => 'tunai',
            'amount_paid' => 500000,
        ]);

        $response->assertUnprocessable()->assertJsonValidationErrors('items');
        $this->assertDatabaseCount('sales', 0);
        $this->assertDatabaseCount('sale_items', 0);
        $this->assertDatabaseCount('stock_movements', 0);
        $this->assertSame(5.0, (float) $this->product->fresh()->stock);
    }

    public function test_service_item_does_not_change_goods_stock(): void
    {
        $service = Product::query()->create([
            'name' => 'Jasa instalasi',
            'type' => 'jasa',
            'purchase_price' => 0,
            'selling_price' => 75000,
            'stock' => 0,
            'minimum_stock' => 0,
            'unit' => 'jasa',
            'is_active' => true,
        ]);

        $this->actingAs($this->cashier)->postJson(route('sales.store'), [
            'items' => [['product_id' => $service->id, 'quantity' => 1]],
            'discount_type' => 'none',
            'discount_value' => 0,
            'payment_method' => 'qris',
            'amount_paid' => 0,
        ])->assertCreated();

        $this->assertSame(5.0, (float) $this->product->fresh()->stock);
        $this->assertDatabaseCount('stock_movements', 0);
    }
}
