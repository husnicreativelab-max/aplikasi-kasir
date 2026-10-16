<?php

namespace Tests\Feature;

use App\Models\Sale;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OperationalHardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_route_redirects_to_login_or_dashboard_without_a_route_closure(): void
    {
        $this->get(route('home'))->assertRedirect(route('login'));

        $admin = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
            'must_change_password' => false,
        ]);

        $this->actingAs($admin)->get(route('home'))->assertRedirect(route('dashboard'));
    }

    public function test_sales_history_includes_the_full_selected_local_date_and_excludes_the_previous_day(): void
    {
        $cashier = User::factory()->create([
            'role' => 'kasir',
            'is_active' => true,
            'must_change_password' => false,
        ]);
        $today = today(config('app.timezone'));
        $todaySale = $this->sale(
            'INV-'.now(config('app.timezone'))->format('Ymd').'-0001',
            $today->copy()->endOfDay(),
            userId: $cashier->id,
        );
        $yesterdaySale = $this->sale(
            'INV-'.now(config('app.timezone'))->format('Ymd').'-0002',
            $today->copy()->subDay()->endOfDay(),
            userId: $cashier->id,
        );

        $this->actingAs($cashier)
            ->get(route('sales.index', ['from' => $today->toDateString(), 'to' => $today->toDateString()]))
            ->assertOk()
            ->assertSee($todaySale->number)
            ->assertDontSee($yesterdaySale->number);
    }

    public function test_report_payment_filter_is_applied_to_all_sales_metrics(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
            'must_change_password' => false,
        ]);
        $today = today(config('app.timezone'));
        $this->sale('INV-'.now(config('app.timezone'))->format('Ymd').'-0001', $today->copy()->addHours(10), 'tunai', 20000, $admin->id);
        $this->sale('INV-'.now(config('app.timezone'))->format('Ymd').'-0002', $today->copy()->addHours(11), 'qris', 35000, $admin->id);

        $this->actingAs($admin)
            ->get(route('reports.index', [
                'from' => $today->toDateString(),
                'to' => $today->toDateString(),
                'payment_method' => 'tunai',
            ]))
            ->assertOk()
            ->assertSee('Rp 20.000')
            ->assertDontSee('Rp 35.000');
    }

    public function test_sales_history_rejects_a_reversed_date_range(): void
    {
        $cashier = User::factory()->create([
            'role' => 'kasir',
            'is_active' => true,
            'must_change_password' => false,
        ]);
        $today = today(config('app.timezone'));

        $this->actingAs($cashier)
            ->get(route('sales.index', [
                'from' => $today->copy()->addDay()->toDateString(),
                'to' => $today->toDateString(),
            ]))
            ->assertSessionHasErrors('from');
    }

    private function sale(
        string $number,
        Carbon $soldAt,
        string $method = 'tunai',
        int $total = 20000,
        ?int $userId = null,
    ): Sale {
        return Sale::query()->create([
            'number' => $number,
            'user_id' => $userId,
            'subtotal' => $total,
            'discount_type' => 'nominal',
            'discount_value' => 0,
            'discount_total' => 0,
            'total' => $total,
            'payment_method' => $method,
            'amount_paid' => $total,
            'change_due' => 0,
            'status' => 'completed',
            'sold_at' => $soldAt,
        ]);
    }
}
