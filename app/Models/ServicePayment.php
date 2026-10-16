<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServicePayment extends Model
{
    protected $fillable = [
        'service_id', 'user_id', 'payment_type', 'payment_method', 'amount',
        'amount_received', 'change_due', 'note', 'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'amount_received' => 'integer',
            'change_due' => 'integer',
            'paid_at' => 'datetime',
        ];
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
