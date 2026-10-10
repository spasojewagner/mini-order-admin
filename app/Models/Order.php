<?php

namespace App\Models;

use App\Enums\OrderStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property OrderStatus $status
 */
class Order extends Model
{
    use HasFactory;

    /**
     * status is not mass assignable on purpose: it changes only through
     * App\Services\OrderWorkflow, which checks the transition and moves stock.
     */
    protected $fillable = [
        'customer_id',
        'total_amount',
        'note',
        'source',
    ];

    protected $attributes = [
        'status' => 'draft',
        'source' => 'admin',
    ];

    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
            'total_amount' => 'decimal:2',
            'confirmed_at' => 'datetime',
            'picking_at' => 'datetime',
            'shipped_at' => 'datetime',
            'delivered_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'invoiced_at' => 'datetime',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(OrderEvent::class)->latest('created_at')->latest('id');
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function isEditable(): bool
    {
        return $this->status->isEditable();
    }

    public function number(): string
    {
        return 'SO-'.str_pad((string) $this->id, 5, '0', STR_PAD_LEFT);
    }

    /** When the order last entered its current status (created_at for draft/new). */
    public function statusSince(): Carbon
    {
        $column = $this->status->timestampColumn();

        return ($column ? $this->{$column} : null) ?? $this->created_at;
    }

    public function scopeStatus(Builder $query, OrderStatus ...$statuses): Builder
    {
        return $query->whereIn('status', array_map(fn (OrderStatus $s) => $s->value, $statuses));
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereIn('status', OrderStatus::openValues());
    }

    public function scopeRevenue(Builder $query): Builder
    {
        return $query->whereIn('status', OrderStatus::revenueValues());
    }
}
