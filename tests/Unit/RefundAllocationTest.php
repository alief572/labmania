<?php

namespace Tests\Unit;

use App\Support\Refund;
use PHPUnit\Framework\TestCase;

class RefundAllocationTest extends TestCase
{
    public function test_allocation_preserves_paid_total_with_stable_remainder(): void
    {
        $this->assertSame([2 => '4', 5 => '3', 9 => '3'], Refund::allocate('10', [9 => '1', 5 => '1', 2 => '1']));
        $this->assertSame([1 => '25', 2 => '76'], Refund::allocate('101', [1 => '1', 2 => '3']));
        $this->assertSame([1 => '3', 2 => '2'], Refund::allocate('5', [1 => '0', 2 => '0']));
        $this->assertSame([1 => '999999999999999998', 2 => '1'], Refund::allocate('999999999999999999', [1 => '999999999999999998', 2 => '1']));
        $this->assertSame([1 => '0'], Refund::allocate('0', [1 => '100']));
    }
}
