<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_cashier_cannot_open_admin_pages_or_create_products(): void
    {
        $cashier = User::factory()->create([
            'role' => 'kasir',
            'is_active' => true,
            'must_change_password' => false,
        ]);

        $this->actingAs($cashier)->get(route('reports.index'))->assertForbidden();
        $this->actingAs($cashier)->get(route('settings.edit'))->assertForbidden();
        $this->actingAs($cashier)->get(route('categories.index'))->assertForbidden();
        $this->actingAs($cashier)->get(route('users.index'))->assertForbidden();
        $this->actingAs($cashier)->get(route('goods-in.index'))->assertForbidden();
        $this->actingAs($cashier)->get(route('products.create'))->assertForbidden();
        $this->actingAs($cashier)->get(route('services.index'))->assertOk();
        $this->actingAs($cashier)->get(route('sales.index'))->assertOk();
    }

    public function test_cashier_can_view_products_without_purchase_price(): void
    {
        $cashier = User::factory()->create([
            'role' => 'kasir',
            'is_active' => true,
            'must_change_password' => false,
        ]);
        $category = Category::query()->create(['name' => 'Komputer', 'is_active' => true]);
        Product::query()->create([
            'category_id' => $category->id,
            'barcode' => 'TEST-ROLE-001',
            'name' => 'Produk Untuk Kasir',
            'type' => 'barang',
            'purchase_price' => 1234567,
            'selling_price' => 2345678,
            'stock' => 4,
            'minimum_stock' => 1,
            'unit' => 'pcs',
            'is_active' => true,
        ]);

        $response = $this->actingAs($cashier)->get(route('products.index'));
        $response->assertOk()->assertSee('Produk Untuk Kasir')->assertSee('Rp 2.345.678')->assertDontSee('Rp 1.234.567');
    }

    public function test_first_login_requires_password_change(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
            'must_change_password' => true,
        ]);

        $this->actingAs($admin)->get(route('dashboard'))->assertRedirect(route('password.change.edit'));
        $this->actingAs($admin)->get(route('password.change.edit'))->assertOk();
    }
}
