<h1 align="center">Runnable</h1>

<p align="center">Run and fake focused PHP classes using Laravel's container.</p>

<p align="center">
<a href="https://github.com/directorytree/runnable/actions"><img src="https://img.shields.io/github/actions/workflow/status/directorytree/runnable/run-tests.yml?branch=master&style=flat-square" alt="Tests"></a>
<a href="https://packagist.org/packages/directorytree/runnable"><img src="https://img.shields.io/packagist/dt/directorytree/runnable.svg?style=flat-square" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/directorytree/runnable"><img src="https://img.shields.io/packagist/v/directorytree/runnable.svg?style=flat-square" alt="Latest Version"></a>
<a href="https://github.com/directorytree/runnable/blob/master/LICENSE.md"><img src="https://img.shields.io/github/license/directorytree/runnable?style=flat-square" alt="License"></a>
</p>

<p align="center">
  <a href="#installation">Installation</a>
  <span> · </span>
  <a href="#usage">Usage</a>
  <span> · </span>
  <a href="#testing">Testing</a>
</p>


---

Runnable gives your action and query classes a familiar way to run and fake their results.

```php
$response = ProcessPayment::run($order);
```

## Requirements

- PHP 8.2 or higher (PHP 8.3 or higher for Laravel 13)
- Laravel 12 or 13

## Installation

```bash
composer require directorytree/runnable
```

The service provider is automatically registered. There is no configuration to publish.

## Usage

Add the `Runnable` trait to a class with a public `handle()` method:

```php
namespace App\Actions;

use App\Contracts\PaymentGateway;
use App\Models\Order;
use App\Payments\PaymentResponse;
use DirectoryTree\Runnable\Runnable;

class ProcessPayment
{
    use Runnable;

    public function __construct(
        protected PaymentGateway $gateway,
    ) {}

    public function handle(Order $order): PaymentResponse
    {
        return $this->gateway->process($order);
    }
}
```

Laravel resolves constructor dependencies through the container. Bind interfaces such as `PaymentGateway` to your implementation as usual.

### Running Classes

Using the trait:

```php
use App\Actions\ProcessPayment;

$response = ProcessPayment::run($order);
```

Using the facade:

```php
use App\Actions\ProcessPayment;
use DirectoryTree\Runnable\Facades\Run;

$response = Run::execute(ProcessPayment::class, $order);
```

Using the helper:

```php
use App\Actions\ProcessPayment;

use function DirectoryTree\Runnable\run;

$response = run(ProcessPayment::class, $order);
```

All three run `handle()` synchronously and return its result. Arguments, including named arguments, are forwarded to `handle()`, and exceptions propagate to the caller. For the facade and helper, pass the class as the first positional argument.

The facade and helper work without the trait. You can also inject your class and call `handle()` directly.

### Running Instances

Using the facade:

```php
use App\Actions\ProcessPayment;
use DirectoryTree\Runnable\Facades\Run;

$response = Run::execute(new ProcessPayment($gateway), $order);
```

Using the helper:

```php
use App\Actions\ProcessPayment;

use function DirectoryTree\Runnable\run;

$response = run(new ProcessPayment($gateway), $order);
```

Runnable executes the supplied instance unless you have registered a fake for its class. Ordinary container bindings do not replace it.

## Testing

Call `fake()` before exercising your application code to replace a runnable's result and verify its calls:

```php
use App\Actions\ProcessPayment;

use function DirectoryTree\Runnable\run;

$fake = ProcessPayment::fake($response);

$result = run(new ProcessPayment($gateway), $order);

expect($result)->toBe($response);

$fake->shouldHaveReceived('handle')->with($order)->once();
```

The same fake works with all three execution styles and subsequent container resolutions, including dependency injection. Use it within Laravel application tests so the container and Mockery are reset between tests.

Fakes intercept `handle()`, so an inline instance's constructor still runs. Calling `handle()` directly on a real instance bypasses Runnable.

The returned fake supports Mockery assertions, including checking that it was never called:

```php
$fake->shouldNotHaveReceived('handle');
```

You can also register a fake through the facade, including for classes without the trait:

```php
use DirectoryTree\Runnable\Facades\Run;

$fake = Run::fake(ProcessPayment::class, $response);
```

Pass `null` explicitly to return `null`. Omitting the result uses Mockery's default for the method's return type. Supplied results must satisfy that type.

### Faking Callbacks

Provide a closure when the result depends on the arguments:

```php
ProcessPayment::fake(
    fn (Order $order) => PaymentResponse::successful($order),
);
```
