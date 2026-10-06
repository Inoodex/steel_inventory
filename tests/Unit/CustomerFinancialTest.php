<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Customer;
use App\Models\Sale;
use App\Models\Payment;
use App\Models\ProductReturn;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CustomerFinancialTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_opening_balance_and_effective_due(): void
    {
        $customer = Customer::create([
            'name' => 'Acme Steel Corp',
            'company' => 'Acme Inc',
            'phone' => '01700000000',
            'opening_balance' => 50000.00,
            'status' => 'active',
        ]);

        $this->assertEquals(50000.00, $customer->opening_balance);
        $this->assertEquals(50000.00, $customer->net_balance);
        $this->assertEquals(50000.00, $customer->effective_due);
        $this->assertEquals(0.00, $customer->advance_credit);
    }

    public function test_customer_advance_credit_calculation(): void
    {
        $customer = Customer::create([
            'name' => 'Alpha Builders',
            'company' => 'Alpha Group',
            'phone' => '01800000000',
            'opening_balance' => 0.00,
            'status' => 'active',
        ]);

        // Customer makes an advance payment of 20000 with 0 sales
        Payment::create([
            'customer_id' => $customer->id,
            'amount' => 20000.00,
            'payment_date' => now()->toDateString(),
            'payment_type' => 'customer',
            'method' => 'cash',
        ]);

        $customer->refresh();

        $this->assertEquals(-20000.00, $customer->net_balance);
        $this->assertEquals(20000.00, $customer->advance_credit);
        $this->assertEquals(0.00, $customer->effective_due);
    }
}
