<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

use Illuminate\Support\Facades\DB;

class Invoice extends Model
{
    protected $fillable = [
        'doc_no',
        'customer_id',
        'customer_name',
        'contact_person',
        'bp_currency',
        'kra_pin',
        'posting_date',
        'value_date',
        'document_date',
        'sales_employee_id',
        'owner_id',
        'remarks',
        'qr_code',
        'total_before_discount',
        'discount_percent',
        'total_down_payment',
        'total_after_discount',
        'freight',
        'rounding',
        'tax',
        'applied_amount',
        'balance_due',
        'payment_order_run',
        'attachments',
    ];

    protected $casts = [
        'posting_date'            => 'date',
        'value_date'              => 'date',
        'document_date'           => 'date',
        'total_before_discount'   => 'decimal:3',
        'discount_percent'        => 'decimal:2',
        'total_down_payment'      => 'decimal:3',
        'total_after_discount'    => 'decimal:3',
        'freight'                 => 'decimal:3',
        'rounding'                => 'boolean',
        'tax'                     => 'decimal:3',
        'applied_amount'          => 'decimal:3',
        'balance_due'             => 'decimal:3',
        'payment_order_run'       => 'boolean',
        'attachments'             => 'array',
    ];
    protected static function booted(): void
    {
        static::creating(function (Invoice $invoice) {
            if (empty($invoice->doc_no)) {
                $invoice->doc_no = static::generateDocNo();
            }
        });
    }

    public static function generateDocNo(): string
    {
        $year = now()->format('y');
        $prefix = "IN{$year}";


        return DB::transaction(function () use ($prefix) {
            $last = static::where('doc_no', 'like', "{$prefix}%")
                ->lockForUpdate()
                ->orderByDesc('doc_no')
                ->value('doc_no');

            $nextSeq = $last
                ? ((int) substr($last, strlen($prefix))) + 1
                : 1;

            return $prefix . str_pad($nextSeq, 6, '0', STR_PAD_LEFT);
        });
    }



    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
    public function salesEmployee(): BelongsTo
    {
        return $this->belongsTo(SalesEmployee::class);
    }
    public function lines(): HasMany
    {
        return $this->hasMany(InvoiceLine::class);
    }

    public function owner(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'owner_id');
    }
}
