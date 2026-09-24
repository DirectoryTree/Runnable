<?php

namespace DirectoryTree\Runnable\Facades;

use DirectoryTree\Runnable\Runner;
use Illuminate\Support\Facades\Facade;

/**
 * @method static mixed execute(mixed ...$arguments)
 * @method static \Mockery\MockInterface fake(string $runnable, mixed $result = null)
 *
 * @see Runner
 */
class Run extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return Runner::class;
    }
}
