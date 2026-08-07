<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Warehouse extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
    ];


    public function invoiceLines(): HasMany
    {
        return $this->hasMany(InvoiceLine::class, 'warehouse_id');
    }
}
