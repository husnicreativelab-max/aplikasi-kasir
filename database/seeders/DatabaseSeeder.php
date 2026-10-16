<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use App\Services\StockLedger;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $defaults = [
                'store_name' => 'Rizky Putri Komputer',
                'short_name' => 'RP COM',
                'tagline' => 'Solusi komputer, layanan bersahabat.',
                'address' => '',
                'phone' => '',
                'whatsapp' => '',
                'theme_color' => '#FFD93D',
                'receipt_footer' => 'Terima kasih sudah berbelanja. Sampai jumpa kembali!',
                'receipt_width_mm' => '58',
                'invoice_prefix' => 'INV',
                'allow_negative_stock' => '0',
                'show_receipt_logo' => '1',
                'logo_path' => '',
                'service_terms' => 'Ketentuan perbaikan dan garansi ditetapkan oleh toko. Silakan tanyakan kepada petugas sebelum menyetujui service.',
            ];

            foreach ($defaults as $key => $value) {
                Setting::query()->firstOrCreate(['key' => $key], ['value' => $value]);
            }

            $email = env('ADMIN_EMAIL', 'admin@rpcom.local');
            $password = env('ADMIN_PASSWORD', 'ChangeMe123!');
            User::query()->firstOrCreate(
                ['email' => $email],
                [
                    'name' => 'Pemilik Toko',
                    'password' => $password,
                    'role' => 'admin',
                    'is_active' => true,
                    'must_change_password' => true,
                ],
            );

            $categories = collect([
                'Komputer & Laptop',
                'Aksesoris',
                'Penyimpanan',
                'Jasa & Layanan',
            ])->mapWithKeys(fn (string $name) => [$name => Category::query()->firstOrCreate(['name' => $name], ['is_active' => true])]);

            $samples = [
                ['barcode' => 'BRG-00000001', 'name' => 'Mouse Wireless Basic', 'category' => 'Aksesoris', 'purchase_price' => 45000, 'selling_price' => 65000, 'stock' => 12],
                ['barcode' => 'BRG-00000002', 'name' => 'Keyboard USB', 'category' => 'Aksesoris', 'purchase_price' => 80000, 'selling_price' => 115000, 'stock' => 8],
                ['barcode' => 'BRG-00000003', 'name' => 'SSD SATA 256 GB', 'category' => 'Penyimpanan', 'purchase_price' => 280000, 'selling_price' => 350000, 'stock' => 5],
                ['barcode' => 'BRG-00000004', 'name' => 'Kabel HDMI 1,5 m', 'category' => 'Aksesoris', 'purchase_price' => 25000, 'selling_price' => 40000, 'stock' => 2],
                ['barcode' => 'BRG-00000005', 'name' => 'Adaptor Laptop Universal', 'category' => 'Komputer & Laptop', 'purchase_price' => 95000, 'selling_price' => 135000, 'stock' => 4],
            ];

            $ledger = app(StockLedger::class);
            foreach ($samples as $sample) {
                $product = Product::query()->firstOrCreate(
                    ['barcode' => $sample['barcode']],
                    [
                        'category_id' => $categories[$sample['category']]->id,
                        'name' => $sample['name'],
                        'type' => 'barang',
                        'purchase_price' => $sample['purchase_price'],
                        'selling_price' => $sample['selling_price'],
                        'stock' => 0,
                        'minimum_stock' => 3,
                        'unit' => 'pcs',
                        'is_active' => true,
                    ],
                );

                if ($product->wasRecentlyCreated && $sample['stock'] > 0) {
                    $ledger->move($product->id, 'in', $sample['stock'], 'opening', Product::class, $product->id, 'Stok contoh awal');
                }
            }
        });
    }
}
