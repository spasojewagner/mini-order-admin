<?php

namespace App\Models;

use App\Enums\OrderStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderEvent extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'order_id',
        'user_id',
        'type',
        'from_status',
        'to_status',
        'via',
        'description',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'from_status' => OrderStatus::class,
            'to_status' => OrderStatus::class,
            'created_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** One line for the timeline, e.g. "Ana (sales) via admin: New → Confirmed". */
    public function summary(): string
    {
        $who = $this->user?->name ?? match ($this->via) {
            'agent' => 'AI agent',
            'portal' => 'Customer',
            default => 'System',
        };

        $what = match ($this->type) {
            'created' => 'Order created',
            'status_changed' => ($this->from_status?->label() ?? '—').' → '.($this->to_status?->label() ?? '—'),
            'invoiced' => 'Invoice issued',
            'email_sent' => 'Email sent to customer',
            'email_failed' => 'Email to customer failed',
            default => ucfirst(str_replace('_', ' ', $this->type)),
        };

        return "{$what} · {$who} via {$this->via}";
    }
}
