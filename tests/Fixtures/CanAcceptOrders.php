<?php

namespace DirectoryTree\Runnable\Tests\Fixtures;

use DirectoryTree\Runnable\Runnable;

class CanAcceptOrders
{
    use Runnable;

    public function handle(): bool
    {
        return true;
    }
}
