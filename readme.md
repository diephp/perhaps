<p align="center">
<a href="https://packagist.org/packages/diephp/perhaps"><img src="https://img.shields.io/packagist/dt/diephp/perhaps" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/diephp/perhaps"><img src="https://img.shields.io/packagist/v/diephp/perhaps" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/diephp/perhaps"><img src="https://img.shields.io/packagist/l/diephp/perhaps" alt="License"></a>
</p>

# Perhaps Retry Library

Perhaps is a retry library for Laravel `9` to `13` and for plain PHP projects without Laravel.

It helps you safely repeat operations that may fail temporarily: HTTP requests, external API calls, synchronization tasks, background jobs, cron processes, connections, and other unstable integrations. Instead of failing immediately, you can retry the same action several times and control the delay between attempts.

## Why use this package

The main advantage of `diephp/perhaps` is support for a `Traversable` delay sequence.

That means you are not limited to a single fixed delay between retries. You can pass a sequence where every next retry uses its own interval. This is especially useful when working with external services that may:

- respond with temporary errors;
- become unavailable for a short time;
- recover gradually after overload;
- require a softer backoff strategy instead of aggressive repeated requests.

With `delaySequence` you can describe growing retry intervals using mathematical sequences such as logarithmic, progressive, exponential, random, or any custom iterator that implements `Traversable`.

This can be more flexible than Laravel's built-in retry helper when you need a fully described retry schedule instead of one repeated delay value.

## Installation

Install the package with Composer:

```sh
composer require diephp/perhaps
```

## Laravel Installation

### Laravel 9-11

Register the service provider in `config/app.php`:

```php
'providers' => [
    // ...
    DiePHP\Perhaps\Providers\PerhapsServiceProvider::class,
],
```

### Laravel v12, v13+

Register the provider in `bootstrap/providers.php`:

```php
<?php

return [
    App\Providers\AppServiceProvider::class, // standard Laravel provider
    // ...
    DiePHP\Perhaps\Providers\PerhapsServiceProvider::class, // Perhaps provider
];
```

### Optional configuration publish

If you want to customize logging or excluded exceptions, publish the config:

```sh
php artisan vendor:publish --tag=perhaps
```

## Usage Without Laravel

This package can also be used without Laravel. In that case, just create the service manually:

```php
use DiePHP\Perhaps\Services\PerhapsService;

$perhaps = new PerhapsService();

$perhaps->retry(function () {
    // Your logic here
}, 3);
```

You can also pass your own PSR logger, log level, and excluded exception list through the constructor if needed.

## Usage

### Basic Laravel usage

```php
use DiePHP\Perhaps\Facades\Perhaps;

$result = Perhaps::retry(function () {
    // Your logic here
    return $this->apiService->getData();
}, 3);
```

The callback receives the current attempt number:

```php
Perhaps::retry(function (int $attempt) {
    if ($attempt < 3) {
        throw new Exception('Temporary failure');
    }

    return 'Success';
}, 5);
```

### Basic PHP usage

```php
use DiePHP\Perhaps\Services\PerhapsService;

$perhaps = new PerhapsService();

$result = $perhaps->retry(function (int $attempt) {
    if ($attempt < 3) {
        throw new RuntimeException('API is temporarily unavailable');
    }

    return 'ok';
}, 5);
```

## Delay Sequences

You can pass any `Traversable` object as the third argument of `retry()`.

Each value from the sequence is used as a delay before the next retry. Delays are passed to `usleep()`, so the values are in microseconds.

This is useful when:

- you are sending requests to external APIs that may recover after a short pause;
- you need to retry jobs or cron synchronization tasks with different intervals;
- you want a gradual backoff instead of repeating requests too aggressively;
- you want to keep retrying without failing too early during temporary incidents.

Our sequence library: [diephp/sequences](https://github.com/diephp/sequences)

## LogarithmicSequence

```php
use DiePHP\Perhaps\Facades\Perhaps;
use DiePHP\Sequences\LogarithmicSequence;

Perhaps::retry(function () {
    // Your logic here
}, 10, new LogarithmicSequence(1000000, 100));
```

`LogarithmicSequence` is useful when each next retry should wait noticeably longer than the previous one. This works well for unstable remote services where immediate repeated retries would only increase load and still fail.

### Retry result table by `LogarithmicSequence(1000000, 100)`

| **Microseconds** | **Seconds** | **Minutes** | **Hours** |
| --- | --- | --- | --- |
| 1,000,000 | 1 | 0.02 | 0.0003 |
| 2,866,748 | 2.87 | 0.048 | 0.0008 |
| 8,218,243 | 8.22 | 0.137 | 0.0023 |
| 23,559,627 | 23.56 | 0.393 | 0.0065 |
| 67,539,499 | 67.54 | 1.126 | 0.0188 |
| 193,618,682 | 193.62 | 3.227 | 0.0538 |
| 555,055,849 | 555.06 | 9.251 | 0.1542 |
| 1,591,204,899 | 1,591.20 | 26.520 | 0.4420 |
| 4,561,582,468 | 4,561.58 | 76.026 | 1.2671 |
| 13,076,904,567 | 13,076.90 | 217.948 | 3.6325 |

## ProgressiveSequence

```php
use DiePHP\Perhaps\Facades\Perhaps;
use DiePHP\Sequences\ProgressiveSequence;

Perhaps::retry(function () {
    // Your logic here
}, 20, new ProgressiveSequence(1000000, 100));
```

`ProgressiveSequence` grows step by step and is useful when you want retry delays to increase steadily without becoming too aggressive too early.

### Retry result table by `ProgressiveSequence(1000000, 100)`

| **Microseconds** | **Seconds** | **Minutes** | **Hours** |
| --- | --- | --- | --- |
| 1,000,000 | 1 | 0.02 | 0.0003 |
| 2,000,000 | 2 | 0.03 | 0.0006 |
| 4,000,000 | 4 | 0.07 | 0.0011 |
| 7,000,000 | 7 | 0.12 | 0.0019 |
| 11,000,000 | 11 | 0.18 | 0.0031 |
| 16,000,000 | 16 | 0.27 | 0.0044 |
| 22,000,000 | 22 | 0.37 | 0.0061 |
| 29,000,000 | 29 | 0.48 | 0.0081 |
| 37,000,000 | 37 | 0.62 | 0.0103 |
| 46,000,000 | 46 | 0.77 | 0.0128 |
| 56,000,000 | 56 | 0.93 | 0.0156 |
| 67,000,000 | 67 | 1.12 | 0.0186 |
| 79,000,000 | 79 | 1.32 | 0.0220 |
| 92,000,000 | 92 | 1.53 | 0.0255 |
| 106,000,000 | 106 | 1.77 | 0.0294 |
| 121,000,000 | 121 | 2.02 | 0.0336 |
| 137,000,000 | 137 | 2.28 | 0.0380 |
| 154,000,000 | 154 | 2.57 | 0.0428 |
| 172,000,000 | 172 | 2.87 | 0.0478 |
| 191,000,000 | 191 | 3.18 | 0.0531 |

## RandSequence

```php
use DiePHP\Perhaps\Facades\Perhaps;
use DiePHP\Sequences\RandSequence;

Perhaps::retry(function () {
    // Your logic here
}, 10, new RandSequence(1000000, 90000000));
```

`RandSequence` can be useful when you want to avoid sending retries at predictable fixed intervals.

| **Microseconds** | **Seconds** | **Minutes** | **Hours** |
| --- | --- | --- | --- |
| 2,000,000 | 2.00 | 0.03 | 0.0006 |
| 82,904,223 | 82.90 | 1.38 | 0.023 |
| 38,693,298 | 38.69 | 0.64 | 0.0108 |
| 32,757,673 | 32.76 | 0.55 | 0.0091 |
| 15,554,548 | 15.55 | 0.26 | 0.0043 |
| 21,913,923 | 21.91 | 0.37 | 0.0061 |
| 56,585,798 | 56.59 | 0.94 | 0.0157 |
| 31,320,173 | 31.32 | 0.52 | 0.0087 |
| 25,867,048 | 25.87 | 0.43 | 0.0072 |
| 5,976,423 | 5.98 | 0.10 | 0.0017 |

## ExponentialSequence

```php
use DiePHP\Perhaps\Facades\Perhaps;
use DiePHP\Sequences\ExponentialSequence;

Perhaps::retry(function () {
    // Your logic here
}, 10, new ExponentialSequence(10, 100));
```

`ExponentialSequence` is suitable when each new retry should back off much more aggressively than the previous one.

| **Microseconds** | **Seconds** | **Minutes** | **Hours** |
| --- | --- | --- | --- |
| 10 | 0.00001 | 0.00000017 | 0.000000003 |
| 20 | 0.00002 | 0.00000033 | 0.000000006 |
| 400 | 0.0004 | 0.00000667 | 0.000000111 |
| 8,000 | 0.008 | 0.00013333 | 0.00000222 |
| 160,000 | 0.16 | 0.00267 | 0.0000444 |
| 3,200,000 | 3.2 | 0.05333 | 0.00089 |
| 64,000,000 | 64.00 | 1.06667 | 0.01778 |
| 1,280,000,000 | 1,280.00 | 21.33 | 0.35556 |
| 25,600,000,000 | 25,600.00 | 426.67 | 7.1111 |
| 512,000,000,000 | 512,000.00 | 8,533.33 | 142.222 |

## Example With Exception Handling

```php
use DiePHP\Perhaps\Facades\Perhaps;

Perhaps::retry(function (int $attempt) {
    if ($attempt < 3) {
        throw new Exception('Simulated failure');
    }

    echo "Success on attempt {$attempt}";
}, 5);
```

```php
use DiePHP\Perhaps\Facades\Perhaps;
use DiePHP\Sequences\LogarithmicSequence;

Perhaps::retry(function () use ($data) {
    History::create($data);
}, 3, new LogarithmicSequence(1000000, 70));
```

## Configuration

If you publish the config, you can customize:

- `errorLogType`
- `excludeExceptions`

Configuration file:

```php
config/perhaps.php
```

## License

This package is open-source software licensed under the [MIT license](LICENSE).
