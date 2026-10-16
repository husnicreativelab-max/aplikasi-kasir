<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Product;
use App\Models\Service;
use App\Models\ServiceItem;
use App\Models\ServicePayment;
use App\Models\ServiceStatusLog;
use App\Models\User;
use App\Support\ActivityLogger;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ServiceWorkflowService
{
    private const TRANSITIONS = [
        'Diterima' => ['Dicek', 'Menunggu Konfirmasi', 'Dikerjakan'],
        'Dicek' => ['Menunggu Konfirmasi', 'Dikerjakan'],
        'Menunggu Konfirmasi' => ['Dicek', 'Dikerjakan'],
        'Dikerjakan' => ['Menunggu Konfirmasi', 'Selesai'],
        'Selesai' => ['Dikerjakan', 'Diambil'],
        'Diambil' => [],
        'Batal' => [],
    ];

    public function __construct(
        private readonly StockLedger $stockLedger,
        private readonly DocumentNumberService $documentNumbers,
    ) {}

    /** @param array<string, mixed> $data */
    public function register(array $data, User $user): Service
    {
        return DB::transaction(function () use ($data, $user): Service {
            $customer = $this->resolveCustomer($data);
            $deposit = (int) $data['deposit_amount'];
            $depositMethod = (string) $data['deposit_method'];
            $amountReceived = $depositMethod === 'tunai'
                ? (int) ($data['deposit_received'] ?? $deposit)
                : $deposit;

            if ($deposit > 0 && $depositMethod === 'tunai' && $amountReceived < $deposit) {
                throw ValidationException::withMessages(['deposit_received' => 'Uang muka tunai yang diterima belum mencukupi.']);
            }

            $trackingCode = Str::upper(Str::random(24));
            $service = Service::query()->create([
                'number' => $this->documentNumbers->next('SRV'),
                'tracking_code_hash' => hash('sha256', $trackingCode),
                'tracking_code_encrypted' => Crypt::encryptString($trackingCode),
                'customer_id' => $customer->id,
                'received_by' => $user->id,
                'customer_name' => $data['customer_name'],
                'customer_phone' => $data['customer_phone'],
                'device_type' => $data['device_type'],
                'brand_model' => $data['brand_model'],
                'serial_number' => $data['serial_number'] ?? null,
                'complaint' => $data['complaint'],
                'accessories' => $data['accessories'] ?? null,
                'device_password' => filled($data['device_password'] ?? null)
                    ? Crypt::encryptString((string) $data['device_password'])
                    : null,
                'estimated_cost' => (int) $data['estimated_cost'],
                'deposit_amount' => $deposit,
                'paid_total' => $deposit,
                'status' => 'Diterima',
                'received_at' => now(config('app.timezone')),
            ]);

            if ($deposit > 0) {
                ServicePayment::query()->create([
                    'service_id' => $service->id,
                    'user_id' => $user->id,
                    'payment_type' => 'dp',
                    'payment_method' => $depositMethod,
                    'amount' => $deposit,
                    'amount_received' => $amountReceived,
                    'change_due' => $depositMethod === 'tunai' ? $amountReceived - $deposit : 0,
                    'note' => 'Uang muka saat perangkat diterima.',
                    'paid_at' => now(config('app.timezone')),
                ]);
            }

            $this->logStatus($service, $user, 'Diterima', $data['notes'] ?? 'Perangkat diterima dari pelanggan.');
            ActivityLogger::log('service.created', Service::class, $service->id, "Service {$service->number} diterima.", [
                'customer_name' => $service->customer_name,
                'device' => $service->brand_model,
                'deposit_amount' => $deposit,
            ], $user->id);

            return $service->load(['customer', 'receivedBy', 'items', 'payments', 'statusLogs.user']);
        }, attempts: 3);
    }

    /** @param array<string, mixed> $data */
    public function addPart(Service $service, array $data, User $user): ServiceItem
    {
        return DB::transaction(function () use ($service, $data, $user): ServiceItem {
            $lockedService = Service::query()->whereKey($service->id)->lockForUpdate()->firstOrFail();
            $this->ensureWorkEditable($lockedService);

            $product = Product::query()
                ->available()
                ->goods()
                ->whereKey((int) $data['product_id'])
                ->lockForUpdate()
                ->firstOrFail();
            $quantity = round((float) $data['quantity'], 3);
            $unitPrice = (int) $product->selling_price;
            $lineTotal = (int) round($unitPrice * $quantity);
            $description = $product->name;
            if (filled($data['note'] ?? null)) {
                $description .= ' — '.trim((string) $data['note']);
            }
            $description = mb_substr($description, 0, 180);

            $item = ServiceItem::query()->create([
                'service_id' => $lockedService->id,
                'product_id' => $product->id,
                'created_by' => $user->id,
                'item_type' => 'sparepart',
                'description' => $description,
                'unit' => $product->unit,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'purchase_price' => (int) $product->purchase_price,
                'line_total' => $lineTotal,
            ]);

            $this->stockLedger->move(
                productId: $product->id,
                direction: 'out',
                quantity: $quantity,
                movementType: 'service_part',
                referenceType: Service::class,
                referenceId: $lockedService->id,
                note: "Sparepart {$description} untuk {$lockedService->number}",
            );

            ActivityLogger::log('service.part_added', Service::class, $lockedService->id, "Sparepart ditambahkan ke {$lockedService->number}.", [
                'product_id' => $product->id,
                'quantity' => $quantity,
                'line_total' => $lineTotal,
            ], $user->id);

            return $item->load('product');
        }, attempts: 3);
    }

    /** @param array<string, mixed> $data */
    public function addLabor(Service $service, array $data, User $user): ServiceItem
    {
        return DB::transaction(function () use ($service, $data, $user): ServiceItem {
            $lockedService = Service::query()->whereKey($service->id)->lockForUpdate()->firstOrFail();
            $this->ensureWorkEditable($lockedService);
            $quantity = round((float) $data['quantity'], 3);
            $unitPrice = (int) $data['unit_price'];
            $lineTotal = (int) round($unitPrice * $quantity);

            $item = ServiceItem::query()->create([
                'service_id' => $lockedService->id,
                'product_id' => null,
                'created_by' => $user->id,
                'item_type' => 'jasa',
                'description' => trim((string) $data['description']),
                'unit' => trim((string) $data['unit']),
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'purchase_price' => 0,
                'line_total' => $lineTotal,
            ]);

            ActivityLogger::log('service.labor_added', Service::class, $lockedService->id, "Biaya jasa ditambahkan ke {$lockedService->number}.", [
                'description' => $item->description,
                'line_total' => $lineTotal,
            ], $user->id);

            return $item;
        }, attempts: 3);
    }

    /** @param array<string, mixed> $data */
    public function changeStatus(Service $service, array $data, User $user): Service
    {
        return DB::transaction(function () use ($service, $data, $user): Service {
            $lockedService = Service::query()->whereKey($service->id)->lockForUpdate()->firstOrFail();
            $previousStatus = $lockedService->status;
            $nextStatus = (string) $data['status'];
            $allowed = self::TRANSITIONS[$previousStatus] ?? [];

            if (! in_array($nextStatus, $allowed, true)) {
                throw ValidationException::withMessages(['status' => "Status tidak dapat diubah dari {$lockedService->status} menjadi {$nextStatus}."]);
            }

            if ($nextStatus === 'Diambil') {
                $lockedService->load('items');
                if ($lockedService->balanceDue() > 0) {
                    throw ValidationException::withMessages(['status' => 'Pelunasan harus dicatat sebelum perangkat diserahkan kepada pelanggan.']);
                }
            }

            $changes = ['status' => $nextStatus];
            if ($nextStatus === 'Selesai') {
                $changes['completed_at'] = now(config('app.timezone'));
            } elseif ($lockedService->status === 'Selesai' && $nextStatus === 'Dikerjakan') {
                $changes['completed_at'] = null;
            }
            if ($nextStatus === 'Diambil') {
                $changes['taken_at'] = now(config('app.timezone'));
            }

            $lockedService->forceFill($changes)->save();
            $this->logStatus($lockedService, $user, $nextStatus, $data['note'] ?? null);
            ActivityLogger::log('service.status_changed', Service::class, $lockedService->id, "Status {$lockedService->number} menjadi {$nextStatus}.", [
                'from' => $previousStatus,
                'to' => $nextStatus,
            ], $user->id);

            return $lockedService->load(['customer', 'items', 'payments', 'statusLogs.user', 'receivedBy']);
        }, attempts: 3);
    }

    /** @param array<string, mixed> $data */
    public function updateEstimate(Service $service, array $data, User $user): Service
    {
        return DB::transaction(function () use ($service, $data, $user): Service {
            $lockedService = Service::query()->whereKey($service->id)->lockForUpdate()->firstOrFail();
            $this->ensureWorkEditable($lockedService);
            $previousEstimate = (int) $lockedService->estimated_cost;
            $newEstimate = (int) $data['estimated_cost'];

            $lockedService->forceFill(['estimated_cost' => $newEstimate])->save();
            $note = trim((string) ($data['note'] ?? ''));
            $logNote = "Estimasi diperbarui dari Rp ".number_format($previousEstimate, 0, ',', '.')." menjadi Rp ".number_format($newEstimate, 0, ',', '.').'.';
            if ($note !== '') {
                $logNote .= ' '.$note;
            }
            $this->logStatus($lockedService, $user, $lockedService->status, $logNote, isPublic: false);
            ActivityLogger::log('service.estimate_updated', Service::class, $lockedService->id, "Estimasi service {$lockedService->number} diperbarui.", [
                'from' => $previousEstimate,
                'to' => $newEstimate,
            ], $user->id);

            return $lockedService->load(['customer', 'items', 'payments', 'statusLogs.user', 'receivedBy']);
        }, attempts: 3);
    }

    /** @param array<string, mixed> $data */
    public function recordPayment(Service $service, array $data, User $user): ServicePayment
    {
        return DB::transaction(function () use ($service, $data, $user): ServicePayment {
            $lockedService = Service::query()->whereKey($service->id)->lockForUpdate()->firstOrFail();
            $this->ensurePaymentable($lockedService);
            $lockedService->load('items');

            $amount = (int) $data['amount'];
            $balance = $lockedService->balanceDue();
            if ($balance <= 0) {
                throw ValidationException::withMessages(['amount' => 'Service ini sudah lunas.']);
            }
            if ($amount > $balance) {
                throw ValidationException::withMessages(['amount' => 'Jumlah pelunasan tidak boleh melebihi sisa tagihan Rp '.number_format($balance, 0, ',', '.').'.']);
            }

            $method = (string) $data['payment_method'];
            $amountReceived = $method === 'tunai' ? (int) ($data['amount_received'] ?? $amount) : $amount;
            if ($method === 'tunai' && $amountReceived < $amount) {
                throw ValidationException::withMessages(['amount_received' => 'Uang yang diterima belum mencukupi.']);
            }

            $payment = ServicePayment::query()->create([
                'service_id' => $lockedService->id,
                'user_id' => $user->id,
                'payment_type' => $amount === $balance ? 'pelunasan' : 'pembayaran',
                'payment_method' => $method,
                'amount' => $amount,
                'amount_received' => $amountReceived,
                'change_due' => $method === 'tunai' ? $amountReceived - $amount : 0,
                'note' => $data['note'] ?? null,
                'paid_at' => now(config('app.timezone')),
            ]);

            $lockedService->forceFill(['paid_total' => (int) $lockedService->paid_total + $amount])->save();
            ActivityLogger::log('service.payment_recorded', Service::class, $lockedService->id, "Pembayaran service {$lockedService->number} dicatat.", [
                'amount' => $amount,
                'payment_method' => $method,
            ], $user->id);

            return $payment;
        }, attempts: 3);
    }

    public function cancel(Service $service, string $reason, User $admin): Service
    {
        return DB::transaction(function () use ($service, $reason, $admin): Service {
            $lockedService = Service::query()->whereKey($service->id)->lockForUpdate()->firstOrFail();
            if (in_array($lockedService->status, ['Batal', 'Diambil'], true)) {
                throw ValidationException::withMessages(['service' => 'Service yang sudah batal atau diambil tidak dapat dibatalkan.']);
            }

            $lockedService->forceFill([
                'status' => 'Batal',
                'cancelled_at' => now(config('app.timezone')),
                'cancelled_by' => $admin->id,
                'cancellation_reason' => trim($reason),
            ])->save();
            $this->logStatus($lockedService, $admin, 'Batal', trim($reason));
            ActivityLogger::log('service.cancelled', Service::class, $lockedService->id, "Service {$lockedService->number} dibatalkan oleh Admin.", [
                'reason' => trim($reason),
                'paid_total' => (int) $lockedService->paid_total,
            ], $admin->id);

            return $lockedService->load(['customer', 'items', 'payments', 'statusLogs.user', 'receivedBy', 'cancelledBy']);
        }, attempts: 3);
    }

    /** @param array<string, mixed> $data */
    private function resolveCustomer(array $data): Customer
    {
        if (! empty($data['customer_id'])) {
            $customer = Customer::query()->lockForUpdate()->findOrFail((int) $data['customer_id']);
        } else {
            $customer = Customer::withTrashed()
                ->where('phone', trim((string) $data['customer_phone']))
                ->orderByDesc('id')
                ->lockForUpdate()
                ->first();

            if (! $customer) {
                $customer = new Customer();
            }
        }

        if ($customer->trashed()) {
            $customer->restore();
        }

        $customer->forceFill([
            'name' => trim((string) $data['customer_name']),
            'phone' => trim((string) $data['customer_phone']),
        ])->save();

        return $customer;
    }

    private function ensureWorkEditable(Service $service): void
    {
        if (in_array($service->status, ['Selesai', 'Diambil', 'Batal'], true)) {
            throw ValidationException::withMessages(['service' => 'Sparepart atau biaya jasa tidak dapat diubah setelah service selesai. Ubah status ke Dikerjakan terlebih dahulu.']);
        }
    }

    private function ensurePaymentable(Service $service): void
    {
        if (in_array($service->status, ['Diambil', 'Batal'], true)) {
            throw ValidationException::withMessages(['service' => 'Pembayaran tidak dapat ditambahkan pada service yang sudah diambil atau dibatalkan.']);
        }
    }

    private function logStatus(Service $service, User $user, string $status, ?string $note, bool $isPublic = true): void
    {
        ServiceStatusLog::query()->create([
            'service_id' => $service->id,
            'user_id' => $user->id,
            'status' => $status,
            'note' => $note,
            'is_public' => $isPublic,
            'created_at' => now(config('app.timezone')),
        ]);
    }
}
