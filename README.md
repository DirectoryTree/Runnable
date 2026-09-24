<h1 align="center">Runnable</h1>

<p align="center">Give your PHP classes a place to run.</p>

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

Applications often organize business logic into focused classes, such as actions and queries. Runnable gives these classes a familiar way to be executed and faked, without requiring a base class or interface.

```php
ProcessPayment::run($order);
```

Prefer a facade or helper? You can use those, too:

```php
use DirectoryTree\Runnable\Facades\Run;

use function DirectoryTree\Runnable\run;

Run::execute(ProcessPayment::class, $order);

run(ProcessPayment::class, $order);
```

All three resolve your class through Laravel's container, pass the arguments to `handle()`, and return its result. Execution is synchronous, and exceptions bubble up to the caller.

## Index

- [Requirements](#requirements)
- [Installation](#installation)
- [Usage](#usage)
  - [Creating a Runnable](#creating-a-runnable)
  - [Running Classes](#running-classes)
  - [Running Instances](#running-instances)
  - [Dependency Injection](#dependency-injection)
- [Testing](#testing)
  - [Faking Results](#faking-results)
  - [Faking Callbacks](#faking-callbacks)
  - [Verifying Calls](#verifying-calls)

## Requirements

- PHP 8.2 or higher (PHP 8.3 or higher for Laravel 13)
- Laravel 12 or 13

## Installation

> Runnable is in development and has not been published to Packagist yet. The Composer command below is for the first release. To try this local checkout, add it as a Composer path repository in your application and require `directorytree/runnable:dev-master`.

You can install the package via Composer:

```bash
composer require directorytree/runnable
```

The service provider is automatically registered. There is no configuration to publish.

## Usage

### Creating a Runnable

Add the `Runnable` trait to a class with a `handle()` method:

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

Constructor dependencies are resolved by Laravel's container. Arguments supplied when running the class are passed to `handle()`:

```php
$response = ProcessPayment::run($order);
```

In this example, `PaymentGateway` is an application interface. Bind it to your implementation in the container as you normally would.

You can place runnable classes wherever they belong in your application. The trait does not require an `Actions` directory or a particular naming convention.

### Running Classes

Use the static method, facade, or imported helper:

```php
use App\Actions\ProcessPayment;
use DirectoryTree\Runnable\Facades\Run;

use function DirectoryTree\Runnable\run;

$response = ProcessPayment::run($order);

$response = Run::execute(ProcessPayment::class, $order);

$response = run(ProcessPayment::class, $order);
```

Pass the class or instance as the first positional argument to the facade or helper. Remaining arguments are forwarded to `handle()`, including named arguments.

The facade and helper can execute any class with a public `handle()` method. The trait is only needed for the class's static `run()` and `fake()` methods.

The helper is namespaced, so import it with `use function DirectoryTree\Runnable\run;` before calling it.

### Running Instances

If you already have an instance, pass it directly:

```php
$payment = new ProcessPayment($gateway);

$response = Run::execute($payment, $order);

$response = run($payment, $order);
```

Runnable executes that exact instance. Its constructor dependencies are your responsibility, and container bindings or fakes for its class will not replace it.

### Dependency Injection

Runnable classes can still be injected and called directly:

```php
public function store(Order $order, ProcessPayment $payment)
{
    $response = $payment->handle($order);

    // ...
}
```

The static method is a convenience. Your class remains an ordinary PHP class.

## Testing

Runnable uses Mockery to replace individual classes in Laravel's container.

Use these examples in Laravel application tests, where the application container and Mockery are reset between tests.

### Faking Results

Provide the result you would like `handle()` to return:

```php
$fake = ProcessPayment::fake($response);

expect(ProcessPayment::run($order))->toBe($response);
```

The same fake is used by the facade, helper, and classes resolved through dependency injection:

```php
Run::execute(ProcessPayment::class, $order);

run(ProcessPayment::class, $order);

app(ProcessPayment::class)->handle($order);
```

You can also fake a class through the facade, including classes that do not use the trait:

```php
$fake = Run::fake(ProcessPayment::class, $response);
```

Pass `null` explicitly to return `null`. If you omit the result entirely, Mockery supplies its default return value for the method's declared return type. Supplied results must satisfy that return type.

Fakes replace container resolutions made after registration. They do not replace instances that were already constructed or injected. Register your fake before exercising the application code under test.

### Faking Callbacks

Provide a closure when the result depends on the arguments:

```php
ProcessPayment::fake(
    fn (Order $order) => PaymentResponse::successful($order),
);
```

### Verifying Calls

The returned fake is a Mockery mock, so you can use its assertions:

```php
$fake = ProcessPayment::fake($response);

ProcessPayment::run($order);

$fake->shouldHaveReceived('handle')
    ->once()
    ->with($order);
```

Or verify that a runnable was not called:

```php
$fake->shouldNotHaveReceived('handle');
```
