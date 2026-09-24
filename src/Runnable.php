<?php

namespace DirectoryTree\Runnable;

use Illuminate\Container\Container;
use Mockery\MockInterface;

trait Runnable
{
    /**
     * Resolve and run the runnable.
     */
    public static function run(mixed ...$arguments): mixed
    {
        return Container::getInstance()->make(Runner::class)->execute(static::class, ...$arguments);
    }

    /**
     * Replace the runnable with a fake.
     *
     * @return MockInterface&static
     */
    public static function fake(mixed $result = null): MockInterface
    {
        return Container::getInstance()->make(Runner::class)->fake(static::class, ...func_get_args());
    }
}
