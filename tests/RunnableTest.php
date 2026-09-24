<?php

use DirectoryTree\Runnable\Facades\Run;
use DirectoryTree\Runnable\Runnable;
use DirectoryTree\Runnable\Runner;
use DirectoryTree\Runnable\Tests\Fixtures\CalculateOrderTotal;
use DirectoryTree\Runnable\Tests\Fixtures\CanAcceptOrders;
use DirectoryTree\Runnable\Tests\Fixtures\FindDiscount;
use Illuminate\Container\Container;

use function DirectoryTree\Runnable\run;

it('resolves constructor parameters through the container', function () {
    app()->when(CalculateOrderTotal::class)->needs('$taxRate')->give(0.05);

    expect(CalculateOrderTotal::run(10000))->toBe(10500);
});

it('runs classes through the facade and helper', function () {
    expect(Run::execute(CalculateOrderTotal::class, 10000))->toBe(11300)
        ->and(run(CalculateOrderTotal::class, 10000))->toBe(11300);
});

it('forwards named arguments in every execution style', function () {
    expect(CalculateOrderTotal::run(shipping: 1000, subtotal: 10000))->toBe(12430)
        ->and(Run::execute(CalculateOrderTotal::class, shipping: 1000, subtotal: 10000))->toBe(12430)
        ->and(run(CalculateOrderTotal::class, shipping: 1000, subtotal: 10000))->toBe(12430);
});

it('runs an existing instance with its own constructor values', function () {
    app()->instance(CalculateOrderTotal::class, new CalculateOrderTotal(0.13));

    $runnable = new CalculateOrderTotal(0.05);

    expect(Run::execute($runnable, 10000))->toBe(10500)
        ->and(run($runnable, 10000))->toBe(10500);
});

it('does not require the trait when using the facade or helper', function () {
    $calculateShipping = new class
    {
        public function handle(int $subtotal): int
        {
            return $subtotal >= 10000 ? 0 : 1500;
        }
    };

    expect(Run::execute($calculateShipping, 5000))->toBe(1500)
        ->and(run($calculateShipping::class, 10000))->toBe(0);
});

it('uses container bindings when resolving a runnable', function () {
    app()->bind(CalculateOrderTotal::class, fn () => new CalculateOrderTotal(0.05));

    expect(CalculateOrderTotal::run(10000))->toBe(10500);
});

it('shares the runner between all execution styles', function () {
    $runner = Mockery::mock(Runner::class);
    $runner->shouldReceive('execute')->with(CalculateOrderTotal::class, 10000)->times(3)->andReturn(11500);
    app()->instance(Runner::class, $runner);

    expect(CalculateOrderTotal::run(10000))->toBe(11500)
        ->and(Run::execute(CalculateOrderTotal::class, 10000))->toBe(11500)
        ->and(run(CalculateOrderTotal::class, 10000))->toBe(11500);
});

it('shares a class fake across all execution styles and dependency injection', function () {
    $fake = CalculateOrderTotal::fake(11500);

    expect(CalculateOrderTotal::run(10000))->toBe(11500)
        ->and(Run::execute(CalculateOrderTotal::class, 10000))->toBe(11500)
        ->and(run(CalculateOrderTotal::class, 10000))->toBe(11500)
        ->and(app()->call(fn (CalculateOrderTotal $calculate) => $calculate->handle(10000)))->toBe(11500);

    $fake->shouldHaveReceived('handle')->with(10000)->times(4);
});

it('fakes a result through the facade', function () {
    $fake = Run::fake(CalculateOrderTotal::class, 11500);

    expect(CalculateOrderTotal::run(10000))->toBe(11500);

    $fake->shouldHaveReceived('handle')->with(10000)->once();
});

it('fakes a result using a callback', function () {
    $fake = CalculateOrderTotal::fake(fn (int $subtotal) => $subtotal + 1500);

    expect(run(CalculateOrderTotal::class, 10000))->toBe(11500)
        ->and(run(CalculateOrderTotal::class, 20000))->toBe(21500);

    $fake->shouldHaveReceived('handle')->with(10000)->once();
    $fake->shouldHaveReceived('handle')->with(20000)->once();
});

it('allows an explicit null result', function () {
    expect(FindDiscount::run('SUMMER'))->toBe(20);

    FindDiscount::fake(null);

    expect(FindDiscount::run('SUMMER'))->toBeNull();
});

it('uses Mockery return defaults when no result is supplied', function () {
    CanAcceptOrders::fake();

    expect(CanAcceptOrders::run())->toBeFalse();
});

it('uses Mockery return defaults when faking through the facade', function () {
    Run::fake(CanAcceptOrders::class);

    expect(CanAcceptOrders::run())->toBeFalse();
});

it('intercepts an existing instance when its class is faked', function () {
    $runnable = new CalculateOrderTotal(0.13);
    $fake = CalculateOrderTotal::fake(11500);

    expect(run($runnable, 10000))->toBe(11500)
        ->and(Run::execute($runnable, 10000))->toBe(11500);

    $fake->shouldHaveReceived('handle')->with(10000)->twice();
});

it('lets execution exceptions propagate', function () {
    $chargePayment = new class
    {
        public function handle(int $amount): never
        {
            throw new RuntimeException('The payment gateway is unavailable.');
        }
    };

    expect(fn () => run($chargePayment, 10000))
        ->toThrow(RuntimeException::class, 'The payment gateway is unavailable.');
});

it('does not retain fakes between applications', function () {
    expect(CanAcceptOrders::run())->toBeTrue()
        ->and(run(new CanAcceptOrders))->toBeTrue();
});

it('forwards named arguments that match the execution parameters', function () {
    $workflow = new class
    {
        use Runnable;

        public function handle(string $runnable, array $arguments): array
        {
            return [$runnable, $arguments];
        }
    };

    expect($workflow::run(arguments: ['invoice' => 42], runnable: 'SendInvoice'))
        ->toBe(['SendInvoice', ['invoice' => 42]])
        ->and(Run::execute($workflow::class, arguments: ['invoice' => 42], runnable: 'SendInvoice'))
        ->toBe(['SendInvoice', ['invoice' => 42]])
        ->and(run($workflow::class, arguments: ['invoice' => 42], runnable: 'SendInvoice'))
        ->toBe(['SendInvoice', ['invoice' => 42]])
        ->and(Run::execute($workflow, arguments: ['invoice' => 42], runnable: 'SendInvoice'))
        ->toBe(['SendInvoice', ['invoice' => 42]])
        ->and(run($workflow, arguments: ['invoice' => 42], runnable: 'SendInvoice'))
        ->toBe(['SendInvoice', ['invoice' => 42]]);
});

it('intercepts inline instances with a facade fake', function () {
    $fake = Run::fake(CalculateOrderTotal::class, 11500);

    expect(run(new CalculateOrderTotal(0.05), 10000))->toBe(11500)
        ->and(Run::execute(new CalculateOrderTotal(0.13), 10000))->toBe(11500);

    $fake->shouldHaveReceived('handle')->with(10000)->twice();
});

it('forwards named instance arguments to a fake callback', function () {
    $fake = CalculateOrderTotal::fake(fn (int $subtotal, int $shipping) => $subtotal + $shipping);

    expect(run(new CalculateOrderTotal, shipping: 1500, subtotal: 10000))->toBe(11500)
        ->and(Run::execute(new CalculateOrderTotal, shipping: 1000, subtotal: 20000))->toBe(21000);

    $fake->shouldHaveReceived('handle')->with(10000, 1500)->once();
    $fake->shouldHaveReceived('handle')->with(20000, 1000)->once();
});

it('returns explicit null results for instances', function () {
    FindDiscount::fake(null);

    expect(run(new FindDiscount, 'SUMMER'))->toBeNull()
        ->and(Run::execute(new FindDiscount, 'SUMMER'))->toBeNull();
});

it('uses the latest fake for instances and container resolutions', function () {
    $previous = CalculateOrderTotal::fake(11500);
    $replacement = CalculateOrderTotal::fake(12000);

    expect(run(new CalculateOrderTotal, 10000))->toBe(12000)
        ->and(CalculateOrderTotal::run(10000))->toBe(12000)
        ->and(app(CalculateOrderTotal::class)->handle(10000))->toBe(12000);

    $previous->shouldNotHaveReceived('handle');
    $replacement->shouldHaveReceived('handle')->with(10000)->times(3);
});

it('keeps instance fakes isolated to their runner', function () {
    CanAcceptOrders::fake();

    $runner = new Runner(new Container);

    expect(run(new CanAcceptOrders))->toBeFalse()
        ->and($runner->execute(new CanAcceptOrders))->toBeTrue();
});
