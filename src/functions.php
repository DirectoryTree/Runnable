<?php

namespace DirectoryTree\Runnable;

use Illuminate\Container\Container;

/**
 * Run the class or instance supplied as the first argument.
 */
function run(mixed ...$arguments): mixed
{
    return Container::getInstance()
        ->make(Runner::class)
        ->execute(...$arguments);
}
