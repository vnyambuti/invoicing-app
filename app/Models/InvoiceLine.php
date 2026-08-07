<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceLine extends Model
{
    protected $fillable = [
        'invoice_id',
        'item_id',
        'item_no',
        'item_description',
        'quantity',
        'price_before_discount',
        'discount',
        'price_after_discount',
        'total',
    ];

    protected $casts = [
        'quantity'               => 'decimal:3',
        'price_before_discount'  => 'decimal:3',
        'discount'                => 'decimal:3',
        'price_after_discount'  => 'decimal:3',
        'total'                    => 'decimal:3',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function warehouse(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }
}
