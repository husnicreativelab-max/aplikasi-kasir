<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Contracts\Encryption\DecryptException;

class Service extends Model
{
    public const STATUSES = [
        'Diterima', 'Dicek', 'Menunggu Konfirmasi', 'Dikerjakan', 'Selesai', 'Diambil', 'Batal',
    ];

    public const WORKFLOW_STATUSES = [
        'Diterima', 'Dicek', 'Menunggu Konfirmasi', 'Dikerjakan', 'Selesai', 'Diambil',
    ];

    protected $fillable = [
        'number', 'customer_id', 'received_by', 'customer_name', 'customer_phone',
        'device_type', 'brand_model', 'serial_number', 'complaint', 'accessories',
        'device_password', 'estimated_cost', 'deposit_amount', 'paid_total', 'status',
        'received_at', 'completed_at', 'taken_at', 'cancelled_at', 'cancelled_by', 'cancellation_reason',
        'tracking_code_hash', 'tracking_code_encrypted',
    ];

    protected $hidden = ['device_password', 'tracking_code_hash', 'tracking_code_encrypted'];

    protected function casts(): array
    {
        return [
            'estimated_cost' => 'integer',
            'deposit_amount' => 'integer',
            'paid_total' => 'integer',
            'received_at' => 'datetime',
            'completed_at' => 'datetime',
            'taken_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function receivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(ServiceItem::class);
    }

    public function statusLogs(): HasMany
    {
        return $this->hasMany(ServiceStatusLog::class)->orderByDesc('created_at');
    }

    public function publicStatusLogs(): HasMany
    {
        return $this->hasMany(ServiceStatusLog::class)->where('is_public', true)->orderBy('created_at');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(ServicePayment::class)->orderBy('paid_at');
    }

    public function totalCost(): int
    {
        if ($this->relationLoaded('items')) {
            return $this->items->isNotEmpty()
                ? (int) $this->items->sum('line_total')
                : (int) $this->estimated_cost;
        }

        return $this->items()->exists()
            ? (int) $this->items()->sum('line_total')
            : (int) $this->estimated_cost;
    }

    public function balanceDue(): int
    {
        return max(0, $this->totalCost() - (int) $this->paid_total);
    }

    public function getDevicePasswordForDisplayAttribute(): ?string
    {
        return $this->decryptOptional($this->device_password);
    }

    public function getTrackingCodeForDisplayAttribute(): ?string
    {
        return $this->decryptOptional($this->tracking_code_encrypted);
    }

    private function decryptOptional(?string $value): ?string
    {
        if (! $value) {
            return null;
        }

        try {
            return Crypt::decryptString($value);
        } catch (DecryptException) {
            return null;
        }
    }
}
