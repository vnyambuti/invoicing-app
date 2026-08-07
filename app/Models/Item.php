<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Item extends Model
{
    protected $fillable = [
        'item_no',
        'description',
        'uom_code',
        'unit_price',
    ];

    protected $casts = [
        'unit_price' => 'decimal:3',
    ];

    public function invoiceLines(): HasMany
    {
        return $this->hasMany(InvoiceLine::class);
    }
}
