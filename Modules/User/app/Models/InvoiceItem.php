<?php

namespace Modules\User\app\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


// ==========================================
// InvoiceItem Model
// ==========================================
class InvoiceItem extends Model
{
    protected $fillable = [
        'invoice_id',
        'product_id',
        'product_number',
        'product_name',
        'quantity',
        'unit_of_measure',
        'price_excl_vat',
        'discount',
        //'vat_percentage',
        'price_incl_vat',
        'line_total_excl_vat',
        'line_total_incl_vat',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'price_excl_vat' => 'decimal:2',
        'discount' => 'decimal:2',
//        'vat_percentage' => 'decimal:2',
        'price_incl_vat' => 'decimal:2',
        'line_total_excl_vat' => 'decimal:2',
        'line_total_incl_vat' => 'decimal:2',
    ];

    // Relationships
    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    // Calculate line totals
    public function calculateTotals()
    {
        $discountRate = min(max((float) ($this->discount ?? 0), 0), 100) / 100;

        $discountedExclVat = $this->price_excl_vat * (1 - $discountRate);
        $this->line_total_excl_vat = $discountedExclVat * $this->quantity;

        $discountedInclVat = ($this->price_incl_vat ?? $this->price_excl_vat) * (1 - $discountRate);
        $this->line_total_incl_vat = $discountedInclVat * $this->quantity;
    }

    // Accessor for formatted price
    public function getFormattedPriceAttribute()
    {
        return number_format($this->price_excl_vat, 2);
    }

     //Accessor for formatted line total
    public function getFormattedLineTotalAttribute()
    {
        return number_format($this->line_total_incl_vat, 2);
    }
}

