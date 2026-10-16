<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sale extends Model
{
    protected $fillable = [
        'number', 'user_id', 'subtotal', 'discount_type', 'discount_value', 'discount_total',
        'total', 'payment_method', 'amount_paid', 'change_due', 'status', 'notes', 'sold_at',
    ];

    protected function casts(): array
    {
        return [
            'subtotal' => 'integer',
            'discount_value' => 'decimal:2',
            'discount_total' => 'integer',
            'total' => 'integer',
            'amount_paid' => 'integer',
            'change_due' => 'integer',
            'sold_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', 'completed');
    }
}
