<?php

namespace DirectoryTree\Runnable\Tests\Fixtures;

use DirectoryTree\Runnable\Runnable;

class FindDiscount
{
    use Runnable;

    /**
     * Find the discount percentage for a coupon code.
     */
    public function handle(string $code): ?int
    {
        return $code === 'SUMMER' ? 20 : null;
    }
}
