<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Service;
use App\Models\ServiceStatusLog;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Tests\TestCase;

class PhaseThreeTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $cashier;

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
    }

    public function test_public_service_status_requires_the_secret_code_and_hides_customer_information(): void
    {
        $code = 'AB12CD34EF56GH78JK90MN12';
        $service = Service::query()->create([
            'number' => 'SRV-'.now(config('app.timezone'))->format('Ymd').'-0001',
            'received_by' => $this->cashier->id,
            'customer_name' => 'Rahasia Pelanggan 933',
            'customer_phone' => '081234567890',
            'device_type' => 'Laptop',
            'brand_model' => 'Model Umum X1',
            'serial_number' => 'SERIAL-RAHASIA-933',
            'complaint' => 'Keluhan yang seharusnya tetap rahasia 933',
            'device_password' => Crypt::encryptString('PIN-RAHASIA-933'),
            'estimated_cost' => 250000,
            'status' => 'Dikerjakan',
            'received_at' => now(config('app.timezone')),
            'tracking_code_hash' => hash('sha256', $code),
            'tracking_code_encrypted' => Crypt::encryptString($code),
        ]);

        ServiceStatusLog::query()->create([
            'service_id' => $service->id,
            'user_id' => $this->cashier->id,
            'status' => 'Dicek',
            'note' => 'Pembaruan aman untuk pelanggan',
            'created_at' => now(config('app.timezone')),
            'is_public' => true,
        ]);
        ServiceStatusLog::query()->create([
            'service_id' => $service->id,
            'user_id' => $this->cashier->id,
            'status' => 'Menunggu Konfirmasi',
            'note' => 'CATATAN INTERNAL RAHASIA 933',
            'created_at' => now(config('app.timezone'))->addMinute(),
            'is_public' => false,
        ]);

        $this->actingAs($this->cashier)
            ->get(route('services.show', $service))
            ->assertOk()
            ->assertSee($code)
            ->assertSee('wa.me/6281234567890');
        $this->actingAs($this->cashier)
            ->get(route('services.receipt.intake', $service))
            ->assertOk()
            ->assertSee($code);

        $response = $this->post(route('public.services.check'), [
            'number' => $service->number,
            'tracking_code' => $code,
        ])->assertOk();
        $cacheControlDirectives = array_map('trim', explode(',', $response->headers->get('Cache-Control', '')));
        $this->assertContains('private', $cacheControlDirectives);
        $this->assertContains('no-store', $cacheControlDirectives);
        $this->assertContains('max-age=0', $cacheControlDirectives);

        $response->assertSee($service->number)
            ->assertDontSee($code)
            ->assertSee('Dikerjakan')
            ->assertSee('Dicek')
            ->assertDontSee('Menunggu Konfirmasi')
            ->assertDontSee('Pembaruan aman untuk pelanggan')
            ->assertDontSee('Rahasia Pelanggan 933')
            ->assertDontSee('081234567890')
            ->assertDontSee('SERIAL-RAHASIA-933')
            ->assertDontSee('Keluhan yang seharusnya tetap rahasia 933')
            ->assertDontSee('PIN-RAHASIA-933')
            ->assertDontSee('CATATAN INTERNAL RAHASIA 933');

        $this->from(route('public.services.index'))
            ->post(route('public.services.check'), [
                'number' => $service->number,
                'tracking_code' => 'ZZ12CD34EF56GH78JK90MN12',
            ])->assertRedirect(route('public.services.index'))
            ->assertSessionHasErrors('lookup')
            ->assertSessionHas('_old_input', fn (array $oldInput) => ! array_key_exists('tracking_code', $oldInput));
    }

    public function test_manifest_is_available_and_dynamic_pages_are_not_added_to_service_worker_cache(): void
    {
        $this->get(route('pwa.manifest'))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/manifest+json; charset=UTF-8')
            ->assertJsonPath('lang', 'id-ID')
            ->assertJsonPath('display', 'standalone')
            ->assertJsonPath('icons.0.src', '/icons/icon-192.png');

        $serviceWorker = file_get_contents(public_path('sw.js'));
        $this->assertIsString($serviceWorker);
        $this->assertStringContainsString("if (!isStaticAsset) return;", $serviceWorker);
        $this->assertStringNotContainsString("caches.put('/dashboard'", $serviceWorker);
        $this->assertFileExists(public_path('offline.html'));
        $this->assertFileExists(public_path('icons/icon-192.png'));
        $this->assertFileExists(public_path('icons/icon-512.png'));
    }

    public function test_only_admin_can_download_reports_and_csv_exports_are_filtered_and_safe_for_spreadsheets(): void
    {
        $category = Category::query()->create(['name' => 'Aksesori', 'is_active' => true]);
        $product = Product::query()->create([
            'category_id' => $category->id,
            'barcode' => 'CSV-TEST-001',
            'name' => 'Mouse Uji',
            'type' => 'barang',
            'purchase_price' => 10000,
            'selling_price' => 20000,
            'stock' => 4,
            'minimum_stock' => 1,
            'unit' => 'pcs',
            'is_active' => true,
        ]);
        $today = today(config('app.timezone'))->toDateString();

        $sale = Sale::query()->create([
            'number' => 'INV-'.now(config('app.timezone'))->format('Ymd').'-0001',
            'user_id' => $this->cashier->id,
            'subtotal' => 20000,
            'discount_type' => 'nominal',
            'discount_value' => 0,
            'discount_total' => 0,
            'total' => 20000,
            'payment_method' => 'tunai',
            'amount_paid' => 20000,
            'change_due' => 0,
            'status' => 'completed',
            'sold_at' => now(config('app.timezone')),
        ]);
        SaleItem::query()->create([
            'sale_id' => $sale->id,
            'product_id' => $product->id,
            'product_name' => '=HYPERLINK("https://example.invalid","Produk")',
            'product_type' => 'barang',
            'unit' => 'pcs',
            'barcode' => 'CSV-TEST-001',
            'quantity' => 1,
            'unit_price' => 20000,
            'purchase_price' => 10000,
            'line_total' => 20000,
        ]);
        StockMovement::query()->create([
            'product_id' => $product->id,
            'user_id' => $this->admin->id,
            'movement_type' => 'stock_in',
            'direction' => 'in',
            'quantity' => 2,
            'stock_before' => 2,
            'stock_after' => 4,
            'reference_type' => Product::class,
            'reference_id' => $product->id,
            'note' => 'Penambahan stok uji',
        ]);
        $service = Service::query()->create([
            'number' => 'SRV-'.now(config('app.timezone'))->format('Ymd').'-0002',
            'received_by' => $this->cashier->id,
            'customer_name' => 'Pelanggan Ekspor',
            'customer_phone' => '081298765432',
            'device_type' => 'Printer',
            'brand_model' => 'Laser X2',
            'complaint' => 'Tidak mencetak',
            'estimated_cost' => 150000,
            'paid_total' => 50000,
            'status' => 'Dikerjakan',
            'received_at' => now(config('app.timezone')),
            'tracking_code_hash' => hash('sha256', 'XY12CD34EF56GH78JK90MN12'),
            'tracking_code_encrypted' => Crypt::encryptString('XY12CD34EF56GH78JK90MN12'),
        ]);

        $this->actingAs($this->cashier)
            ->get(route('reports.export.sales', ['from' => $today, 'to' => $today]))
            ->assertForbidden();
        $this->actingAs($this->cashier)
            ->get(route('reports.export.stock', ['from' => $today, 'to' => $today]))
            ->assertForbidden();
        $this->actingAs($this->cashier)
            ->get(route('reports.export.services', ['from' => $today, 'to' => $today]))
            ->assertForbidden();

        $salesResponse = $this->actingAs($this->admin)
            ->get(route('reports.export.sales', ['from' => $today, 'to' => $today, 'payment_method' => 'tunai']))
            ->assertOk()
            ->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $salesCsv = $salesResponse->streamedContent();
        $this->assertStringContainsString('=HYPERLINK', $salesCsv);
        $this->assertStringContainsString("'=HYPERLINK", $salesCsv);
        $this->assertStringContainsString('Laba kotor baris', $salesCsv);
        $this->assertStringContainsString($sale->number, $salesCsv);

        $stockCsv = $this->actingAs($this->admin)
            ->get(route('reports.export.stock', ['from' => $today, 'to' => $today, 'movement_type' => 'stock_in']))
            ->assertOk()
            ->streamedContent();
        $this->assertStringContainsString('Penambahan stok uji', $stockCsv);
        $this->assertStringContainsString('Jenis mutasi', $stockCsv);

        $serviceCsv = $this->actingAs($this->admin)
            ->get(route('reports.export.services', ['from' => $today, 'to' => $today, 'status' => 'Dikerjakan']))
            ->assertOk()
            ->streamedContent();
        $this->assertStringContainsString($service->number, $serviceCsv);
        $this->assertStringContainsString('Pelanggan Ekspor', $serviceCsv);
        $this->assertStringNotContainsString('XY12CD34EF56GH78JK90MN12', $serviceCsv);
        $this->assertStringNotContainsString('PIN', $serviceCsv);
    }
}
