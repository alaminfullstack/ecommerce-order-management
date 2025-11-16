<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class InventoryLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'inventoriable_type',
        'inventoriable_id',
        'type',
        'quantity_before',
        'quantity_after',
        'quantity_changed',
        'reason',
        'user_id',
        'notes',
    ];

    protected $casts = [
        'quantity_before' => 'integer',
        'quantity_after' => 'integer',
        'quantity_changed' => 'integer',
    ];

    /**
     * Get the parent inventoriable model (Product or ProductVariant).
     */
    public function inventoriable()
    {
        return $this->morphTo();
    }

    /**
     * Get the user who made the change.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
