<?php

namespace App\Models;

use App\Events\OrderStatusChanged;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Order extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'order_number',
        'customer_id',
        'status',
        'subtotal',
        'tax',
        'shipping',
        'total',
        'shipping_address',
        'billing_address',
        'notes',
        'confirmed_at',
        'shipped_at',
        'delivered_at',
        'cancelled_at',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'tax' => 'decimal:2',
        'shipping' => 'decimal:2',
        'total' => 'decimal:2',
        'confirmed_at' => 'datetime',
        'shipped_at' => 'datetime',
        'delivered_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    protected $dispatchesEvents = [
        'updated' => OrderStatusChanged::class,
    ];

    /**
     * Relationships
     */
    public function customer()
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Scopes
     */
    public function scopeByCustomer(Builder $query, int $customerId)
    {
        return $query->where('customer_id', $customerId);
    }

    public function scopeByStatus(Builder $query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopePending(Builder $query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeRecent(Builder $query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    /**
     * Status check methods
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isConfirmed(): bool
    {
        return $this->status === 'confirmed';
    }

    public function isProcessing(): bool
    {
        return $this->status === 'processing';
    }

    public function isShipped(): bool
    {
        return $this->status === 'shipped';
    }

    public function isDelivered(): bool
    {
        return $this->status === 'delivered';
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    public function canBeCancelled(): bool
    {
        return in_array($this->status, ['pending', 'confirmed']);
    }

    /**
     * Status transition methods
     */
    public function markAsConfirmed(): void
    {
        // Deduct inventory for each item
        foreach ($this->items as $item) {
            if ($item->product_variant_id) {
                $success = $item->variant->decreaseStock($item->quantity, 'order_confirmation');
            } else {
                $success = $item->product->decreaseStock($item->quantity, 'order_confirmation');
            }

            if (!$success) {
                $productName = $item->product_name ?? $item->product->name ?? 'Unknown Product';
                throw new \Exception("Insufficient stock for product: {$productName}");
            }
        }

        $this->update([
            'status' => 'confirmed',
            'confirmed_at' => now(),
        ]);
    }

    public function markAsProcessing(): void
    {
        $this->update([
            'status' => 'processing',
        ]);
    }

    public function markAsShipped(): void
    {
        $this->update([
            'status' => 'shipped',
            'shipped_at' => now(),
        ]);
    }

    public function markAsDelivered(): void
    {
        $this->update([
            'status' => 'delivered',
            'delivered_at' => now(),
        ]);
    }

    public function markAsCancelled(): void
    {
        if (!$this->canBeCancelled()) {
            throw new \Exception('Order cannot be cancelled in current status');
        }

        $this->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
        ]);
    }

    /**
     * Boot method
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($order) {
            if (empty($order->order_number)) {
                $order->order_number = 'ORD-' . strtoupper(uniqid());
            }
        });
    }
}
