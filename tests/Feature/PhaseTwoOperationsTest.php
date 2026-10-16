<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Service;
use App\Models\ServiceItem;
use App\Models\ServicePayment;
use App\Models\StockIn;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Tests\TestCase;

class PhaseTwoOperationsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $cashier;
    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
            'must_change_password' => false,
        ]);
        $this->cashier = User::factory()->create([
            'role' => 'kasir',
            'is_active' => true,
            'must_change_password' => false,
        ]);
        $this->category = Category::query()->create(['name' => 'Komponen Uji', 'is_active' => true]);
    }

    public function test_admin_stock_in_updates_stock_purchase_price_and_immutable_movement_history(): void
    {
        $product = $this->product(stock: 4, purchasePrice: 30000);

        $response = $this->actingAs($this->admin)->post(route('goods-in.store'), [
            'product_id' => $product->id,
            'quantity' => 2.5,
            'purchase_price' => 42000,
            'note' => 'Pembelian batch Oktober',
        ]);

        $response->assertRedirect(route('goods-in.index'));
        $this->assertSame(6.5, (float) $product->fresh()->stock);
        $this->assertSame(42000, (int) $product->fresh()->purchase_price);
        $this->assertDatabaseHas('stock_ins', [
            'product_id' => $product->id,
            'user_id' => $this->admin->id,
            'quantity' => 2.5,
            'purchase_price' => 42000,
            'total_cost' => 105000,
        ]);
        $stockIn = StockIn::query()->firstOrFail();
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'movement_type' => 'stock_in',
            'direction' => 'in',
            'quantity' => 2.5,
            'stock_before' => 4,
            'stock_after' => 6.5,
            'reference_id' => $stockIn->id,
        ]);
    }

    public function test_cashier_cannot_receive_stock_or_cancel_a_sale(): void
    {
        $product = $this->product();
        $this->actingAs($this->cashier)->get(route('goods-in.index'))->assertForbidden();
        $this->actingAs($this->cashier)->post(route('goods-in.store'), [
            'product_id' => $product->id,
            'quantity' => 1,
            'purchase_price' => 30000,
        ])->assertForbidden();

        $sale = $this->createSale($product);
        $this->actingAs($this->cashier)->post(route('sales.cancel', $sale), ['reason' => 'Retur uji'])->assertForbidden();
        $this->assertSame('completed', $sale->fresh()->status);
        $this->assertSame(2.0, (float) $product->fresh()->stock);
    }

    public function test_service_intake_saves_customer_encrypted_device_password_dp_and_initial_status(): void
    {
        $response = $this->actingAs($this->cashier)->post(route('services.store'), [
            'customer_name' => 'Budi Santoso',
            'customer_phone' => '081234567890',
            'device_type' => 'Laptop',
            'brand_model' => 'ASUS VivoBook 14',
            'serial_number' => 'SN-TEST-01',
            'complaint' => 'Laptop tidak dapat mengisi daya.',
            'accessories' => 'Adaptor daya',
            'device_password' => 'pin-7788',
            'estimated_cost' => 250000,
            'deposit_amount' => 50000,
            'deposit_method' => 'tunai',
            'deposit_received' => 60000,
            'notes' => 'Kondisi fisik baik.',
        ]);

        $service = Service::query()->firstOrFail();
        $response->assertRedirect(route('services.show', $service));
        $this->assertSame('SRV-'.now(config('app.timezone'))->format('Ymd').'-0001', $service->number);
        $this->assertSame('Diterima', $service->status);
        $this->assertSame('Budi Santoso', $service->customer_name);
        $this->assertSame('081234567890', $service->customer_phone);
        $this->assertSame('pin-7788', Crypt::decryptString((string) $service->device_password));
        $this->assertNotSame('pin-7788', $service->device_password);
        $this->assertInstanceOf(Customer::class, $service->customer);
        $this->assertDatabaseHas('service_payments', [
            'service_id' => $service->id,
            'payment_type' => 'dp',
            'amount' => 50000,
            'amount_received' => 60000,
            'change_due' => 10000,
        ]);
        $this->assertDatabaseHas('service_status_logs', [
            'service_id' => $service->id,
            'status' => 'Diterima',
            'note' => 'Kondisi fisik baik.',
        ]);
    }

    public function test_sparepart_and_labor_are_recorded_and_sparepart_reduces_stock(): void
    {
        $service = $this->createService();
        $product = $this->product(stock: 5, sellingPrice: 50000);

        $response = $this->actingAs($this->cashier)->post(route('services.parts.store', $service), [
            'product_id' => $product->id,
            'quantity' => 2,
            'note' => 'SSD 512 GB',
        ]);
        $response->assertRedirect(route('services.show', $service));

        $this->actingAs($this->cashier)->post(route('services.labor.store', $service), [
            'description' => 'Instal ulang sistem operasi',
            'unit' => 'jasa',
            'quantity' => 1,
            'unit_price' => 120000,
        ])->assertRedirect(route('services.show', $service));

        $this->assertSame(3.0, (float) $product->fresh()->stock);
        $this->assertSame(2, ServiceItem::query()->where('service_id', $service->id)->count());
        $this->assertDatabaseHas('service_items', [
            'service_id' => $service->id,
            'item_type' => 'sparepart',
            'line_total' => 100000,
        ]);
        $this->assertDatabaseHas('service_items', [
            'service_id' => $service->id,
            'item_type' => 'jasa',
            'line_total' => 120000,
        ]);
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'movement_type' => 'service_part',
            'direction' => 'out',
            'reference_id' => $service->id,
        ]);
    }

    public function test_service_payment_must_be_completed_before_marking_device_taken(): void
    {
        $service = $this->createService(estimated: 150000, deposit: 50000);
        $this->actingAs($this->cashier)->post(route('services.labor.store', $service), [
            'description' => 'Biaya perbaikan',
            'unit' => 'jasa',
            'quantity' => 1,
            'unit_price' => 150000,
        ]);

        $this->actingAs($this->cashier)->patch(route('services.status.update', $service), [
            'status' => 'Dikerjakan',
        ])->assertRedirect(route('services.show', $service));
        $this->actingAs($this->cashier)->patch(route('services.status.update', $service), [
            'status' => 'Selesai',
        ])->assertRedirect(route('services.show', $service));

        $this->actingAs($this->cashier)->patch(route('services.status.update', $service), [
            'status' => 'Diambil',
        ])->assertSessionHasErrors('status');
        $this->assertSame('Selesai', $service->fresh()->status);

        $this->actingAs($this->cashier)->post(route('services.payment.store', $service), [
            'amount' => 100000,
            'payment_method' => 'transfer',
            'note' => 'Pelunasan transfer',
        ])->assertRedirect(route('services.show', $service));

        $this->actingAs($this->cashier)->patch(route('services.status.update', $service), [
            'status' => 'Diambil',
        ])->assertRedirect(route('services.show', $service));

        $this->assertSame('Diambil', $service->fresh()->status);
        $this->assertSame(0, $service->fresh()->balanceDue());
        $this->assertSame(2, ServicePayment::query()->where('service_id', $service->id)->count());
        $this->assertDatabaseHas('service_payments', [
            'service_id' => $service->id,
            'payment_type' => 'pelunasan',
            'amount' => 100000,
        ]);
        $this->get(route('services.receipt.release', $service))->assertOk()->assertSee('Nota pengambilan service');
    }

    public function test_admin_cancelling_sale_returns_only_goods_stock_and_records_reason(): void
    {
        $product = $this->product(stock: 5);
        $serviceProduct = $this->product(type: 'jasa', stock: 0, sellingPrice: 80000, name: 'Biaya diagnosis');
        $sale = $this->createSale($product, $serviceProduct);
        $this->assertSame(3.0, (float) $product->fresh()->stock);

        $response = $this->actingAs($this->admin)->post(route('sales.cancel', $sale), [
            'reason' => 'Pelanggan membatalkan transaksi.',
        ]);

        $response->assertRedirect(route('sales.receipt', $sale));
        $this->assertSame('cancelled', $sale->fresh()->status);
        $this->assertSame('Pelanggan membatalkan transaksi.', $sale->fresh()->cancellation_reason);
        $this->assertSame(5.0, (float) $product->fresh()->stock);
        $this->assertSame(0.0, (float) $serviceProduct->fresh()->stock);
        $this->assertSame(2, StockMovement::query()->count());
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'movement_type' => 'sale_return',
            'direction' => 'in',
            'quantity' => 2,
            'reference_id' => $sale->id,
        ]);
        $this->get(route('sales.receipt', $sale))->assertOk()->assertSee('TRANSAKSI DIBATALKAN / DIRETUR');
    }

    private function product(
        string $type = 'barang',
        float $stock = 4,
        int $purchasePrice = 30000,
        int $sellingPrice = 50000,
        string $name = 'Mouse Uji',
    ): Product {
        static $sequence = 0;
        $sequence++;

        return Product::query()->create([
            'category_id' => $this->category->id,
            'barcode' => 'PHASE2-'.str_pad((string) $sequence, 3, '0', STR_PAD_LEFT),
            'name' => $name.' '.$sequence,
            'type' => $type,
            'purchase_price' => $purchasePrice,
            'selling_price' => $sellingPrice,
            'stock' => $stock,
            'minimum_stock' => 1,
            'unit' => $type === 'barang' ? 'pcs' : 'jasa',
            'is_active' => true,
        ]);
    }

    private function createService(int $estimated = 200000, int $deposit = 0): Service
    {
        $response = $this->actingAs($this->cashier)->post(route('services.store'), [
            'customer_name' => 'Dewi Lestari',
            'customer_phone' => '081299988877',
            'device_type' => 'Laptop',
            'brand_model' => 'Lenovo IdeaPad',
            'complaint' => 'Perangkat lambat.',
            'estimated_cost' => $estimated,
            'deposit_amount' => $deposit,
            'deposit_method' => 'transfer',
        ]);
        $response->assertRedirect();

        return Service::query()->firstOrFail();
    }

    private function createSale(Product $goods, ?Product $serviceProduct = null): Sale
    {
        $items = [['product_id' => $goods->id, 'quantity' => 2]];
        if ($serviceProduct) {
            $items[] = ['product_id' => $serviceProduct->id, 'quantity' => 1];
        }

        $response = $this->actingAs($this->cashier)->postJson(route('sales.store'), [
            'items' => $items,
            'discount_type' => 'none',
            'discount_value' => 0,
            'payment_method' => 'tunai',
            'amount_paid' => 500000,
        ]);
        $response->assertCreated();

        return Sale::query()->findOrFail(Sale::query()->latest('id')->value('id'));
    }
}
