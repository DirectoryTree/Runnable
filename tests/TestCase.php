<?php

namespace DirectoryTree\Runnable\Tests;

use DirectoryTree\Runnable\RunnableServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [RunnableServiceProvider::class];
    }
}
