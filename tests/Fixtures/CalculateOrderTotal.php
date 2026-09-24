<?php

namespace DirectoryTree\Runnable\Tests\Fixtures;

use DirectoryTree\Runnable\Runnable;

class CalculateOrderTotal
{
    use Runnable;

    public function __construct(
        protected float $taxRate = 0.13
    ) {}

    /**
     * Calculate the order total in cents, including shipping and tax.
     */
    public function handle(int $subtotal, int $shipping = 0): int
    {
        return (int) round(($subtotal + $shipping) * (1 + $this->taxRate));
    }
}
