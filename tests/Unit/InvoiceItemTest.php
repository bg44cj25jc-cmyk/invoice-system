<?php

namespace Tests\Unit;

use Modules\User\app\Models\InvoiceItem;
use PHPUnit\Framework\TestCase;

class InvoiceItemTest extends TestCase
{
    public function test_calculates_discounted_totals_using_exclusive_and_inclusive_prices(): void
    {
        $item = new InvoiceItem([
            'quantity' => 2,
            'price_excl_vat' => 100,
            'price_incl_vat' => 120,
            'discount' => 10,
        ]);

        $item->calculateTotals();

        $this->assertEqualsWithDelta(180, $item->line_total_excl_vat, 0.001);
        $this->assertEqualsWithDelta(216, $item->line_total_incl_vat, 0.001);
    }
}
