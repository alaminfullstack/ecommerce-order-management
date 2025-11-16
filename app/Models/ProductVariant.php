<?php

namespace App\Models;

use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ProductVariant extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'product_id',
        'sku',
        'name',
        'price',
        'stock_quantity',
        'attributes',
        'is_active',
    ];

    protected $casts = [
        'price' => 'decimal:3',
        'stock_quantity' => 'integer',
        'attributes' => 'array',
        'is_active' => 'boolean',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class, 'product_variant_id');
    }

    public function inventoryLogs()
    {
        return $this->morphMany(InventoryLog::class, 'inventoriable');
    }

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
}
