<?php

namespace App\Models;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'vendor_id',
        'name',
        'slug',
        'description',
        'price',
        'sku',
        'stock_quantity',
        'low_stock_threshold',
        'is_active',
        'image',
        'metadata',
    ];

    protected $casts = [
        'price' => 'decimal:3',
        'stock_quantity' => 'integer',
        'low_stock_threshold' => 'integer',
        'is_active' => 'boolean',
        'metadata' => 'array',
    ];

    protected $appends = ['is_low_stock'];

    /**
     * Relationships
     */
    public function vendor()
    {
        return $this->belongsTo(User::class, 'vendor_id');
    }

    public function variants()
    {
        return $this->hasMany(ProductVariant::class, 'product_id');
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class, 'product_id');
    }

    public function inventoryLogs()
    {
        return $this->morphMany(InventoryLog::class, 'inventoriable');
    }

    /**
     * Accessors
     */
    public function getIsLowStockAttribute(): bool
    {
        return $this->stock_quantity <= $this->low_stock_threshold;
    }

    /**
     * Scopes
     */
    public function scopeActive(Builder $query)
    {
        return $query->where('is_active', true);
    }

    public function scopeLowStock(Builder $query)
    {
        return $query->whereColumn('stock_quantity', '<=', 'low_stock_threshold');
    }

    public function scopeSearch(Builder $query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('description', 'like', "%{$search}%")
              ->orWhere('sku', 'like', "%{$search}%");
        });
    }

    public function scopeByVendor(Builder $query, int $vendorId)
    {
        return $query->where('vendor_id', $vendorId);
    }

    /**
     * Business Logic Methods
     */
    public function decreaseStock(int $quantity, string $reason = 'order'): bool
    {
        if ($this->stock_quantity < $quantity) {
            return false;
        }

        $quantityBefore = $this->stock_quantity;
        $this->stock_quantity -= $quantity;
        $this->save();

        $this->inventoryLogs()->create([
            'type' => 'decrease',
            'quantity_before' => $quantityBefore,
            'quantity_after' => $this->stock_quantity,
            'quantity_changed' => $quantity,
            'reason' => $reason,
            'user_id' => Auth::id(),
        ]);

        return true;
    }

    public function increaseStock(int $quantity, string $reason = 'restock'): void
    {
        $quantityBefore = $this->stock_quantity;
        $this->stock_quantity += $quantity;
        $this->save();

        $this->inventoryLogs()->create([
            'type' => 'increase',
            'quantity_before' => $quantityBefore,
            'quantity_after' => $this->stock_quantity,
            'quantity_changed' => $quantity,
            'reason' => $reason,
            'user_id' => Auth::id(),
        ]);
    }

    /**
     * Boot method
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($product) {
            if (empty($product->slug)) {
                $product->slug = Str::slug($product->name . '-' . Str::random(6));
            }
        });
    }
}
