
# Perhaps Retry Library

A Laravel package that provides a simple way to retry any logic with customizable attempts and delays. This can be particularly useful in scenarios where certain operations might fail due to transient issues, such as network errors or temporary service outages.

## Installation

1. Install the package via Composer:
    ```sh
    composer require diephp/perhaps
    ```

2. Register the service provider in your `config/app.php` file:
    ```php
    'providers' => [
        // ...
        \DiePHP\Perhaps\Providers\PerhapsServiceProvider::class,
    ],
    ```
   or for laravel 11+ add Provider to list in bootstrap/providers.php
     ```php
     return [
         App\Providers\AppServiceProvider::class,
         ...
         \DiePHP\Perhaps\Providers\PerhapsServiceProvider::class,
     ];
     ```

3. Optionally, publish the package configuration:
    ```sh
    php artisan vendor:publish --tag=perhaps
    ```

## Usage

### Basic Usage

The main function provided by this package is `Perhaps::retry`. You can use it to retry any logic with a specified number of attempts.

```php
use DiePHP\Perhaps\Facades\Perhaps;

Perhaps::retry(function() {
    // Your logic here
}, 3); // Retry 3 times
```

### Handling Delays

You can also specify a delay sequence using a `Traversable` iterator. This allows you to define custom delay logic between retries.
you can see details on https://github.com/diephp/sequences

```php
use DiePHP\Perhaps\Facades\Perhaps;
use DiePHP\Sequences\ProgressiveSequence;

Perhaps::retry(function() {
    // Your logic here
}, 10, new ProgressiveSequence(1000000, 100)); // Retry 10 times with progressive delay
```

```php
use DiePHP\Perhaps\Facades\Perhaps;
use DiePHP\Sequences\RandSequence;

Perhaps::retry(function() {
    // Your logic here
}, 10, new RandSequence(1000000, 90000000));
```

```php
use DiePHP\Perhaps\Facades\Perhaps;
use DiePHP\Sequences\ExponentialSequence;

Perhaps::retry(function() {
    // Your logic here
}, 1000, new ExponentialSequence(10, 100));
```

### Example with Exception Handling

In this example, the retry logic will handle exceptions and log the retry attempts.

```php
Perhaps::retry(function($attempt) {
    if ($attempt < 3) {
        throw new \Exception("Simulated failure");
    }
    // Successful operation
    echo "Success on attempt $attempt";
}, 5); // Retry 5 times
```

## Configuration

You can configure the package by modifying the published configuration file `config/perhaps.php`. This allows you to customize the logging and exception handling behavior.


## License

This package is open-source software licensed under the [MIT license](LICENSE).


### Key Points

- The `retry` method allows you to retry any callable logic a specified number of times.
- You can specify a delay sequence for custom delay logic between retries.
- Register the service provider and optionally publish the configuration for customization.
- Use the provided facade for easy access to the `retry` method in your Laravel application.
