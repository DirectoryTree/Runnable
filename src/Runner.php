<?php

namespace DirectoryTree\Runnable;

use Closure;
use Illuminate\Contracts\Container\Container;
use Mockery;
use Mockery\MockInterface;

class Runner
{
    /**
     * Create a new runner.
     */
    public function __construct(
        protected Container $container
    ) {}

    /**
     * Run the class or instance supplied as the first argument.
     */
    public function execute(mixed ...$arguments): mixed
    {
        $runnable = array_shift($arguments);

        if (is_string($runnable)) {
            $runnable = $this->container->make($runnable);
        }

        return $runnable->handle(...$arguments);
    }

    /**
     * Replace the runnable in the container with a fake.
     *
     * @template T of object
     *
     * @param  class-string<T>  $runnable
     * @return MockInterface&T
     */
    public function fake(string $runnable, mixed $result = null): MockInterface
    {
        $fake = Mockery::mock($runnable);

        $expectation = $fake->shouldReceive('handle');

        if ($result instanceof Closure) {
            $expectation->andReturnUsing($result);
        } elseif (func_num_args() > 1) {
            $expectation->andReturn($result);
        }

        $this->container->instance($runnable, $fake);

        return $fake;
    }
}
