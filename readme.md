<p align="center">
<a href="https://packagist.org/packages/diephp/perhaps"><img src="https://img.shields.io/packagist/dt/diephp/perhaps" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/diephp/perhaps"><img src="https://img.shields.io/packagist/v/diephp/perhaps" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/diephp/perhaps"><img src="https://img.shields.io/packagist/l/diephp/perhaps" alt="License"></a>
</p>

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

## LogarithmicSequence
```php
use DiePHP\Perhaps\Facades\Perhaps;
use DiePHP\Sequences\LogarithmicSequence;

Perhaps::retry(function() {
    // Your logic here
}, 10, new LogarithmicSequence(1000000, 100)); // Retry 10 times with logarithmicSequence delay
```
### Simple Explanation:
Each value in the sequence is derived from multiplying the previous value by a logarithmic growth factor, which depends on the configured percentage. Squaring the factor introduces a faster scaling effect, and rounding ensures integer progression.
The **result on the 10th iteration** will exceed **3 hours**, which means that by this point, the total time represented by the sequence will have grown significantly.
This feature of the `LogarithmicSequence` can be quite useful when working with remote services that may become temporarily unavailable. In such cases:
- You may not want to **abandon already processed data**, especially if its collection or processing took a considerable effort.
- Instead, the logarithmic growth helps you **wait longer between retries**, which increases the likelihood of success without wasting too much CPU time or bandwidth in continual attempts.

This makes it possible to gracefully handle **delays** and **resilience** in long-running processes when interacting with unreliable external services.

### Retry result table by LogarithmicSequence(1000000, 100)
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

Perhaps::retry(function() {
    // Your logic here
}, 20, new ProgressiveSequence(1000000, 100)); // Retry 10 times with progressive delay
```
### **How `ProgressiveSequence` works (in words)**:
The `ProgressiveSequence` is a number sequence generator where each value grows progressively based on a fixed starting value and a percentage increment.
1. **Starting Value**: The sequence starts with a given base value (`start`).
2. **Growth By Percentage**: For each iteration:
   - The increment is calculated as a percentage of the starting value (`start`).
   - This percentage-based increment grows proportionally with the iteration number, meaning each next value adds **more** than the previous iteration.

3. **Round Calculation**: The calculated result is rounded to the nearest integer to avoid fractional values.
4. **Behavior Over Iterations**: Since the increment grows with each step, the sequence becomes larger and larger — forming a **progressive growth trend**.

### **Key Difference from `LogarithmicSequence`**:
While `LogarithmicSequence` grows based on a logarithmic scaling factor, which slows down over time, the `ProgressiveSequence` grows linearly and **increases consistently** by a calculated percentage.
### **Microseconds Conversion Table**
Here’s the conversion of the provided microseconds values into seconds, minutes, and hours as requested:

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

Perhaps::retry(function() {
    // Your logic here
}, 10, new RandSequence(1000000, 90000000));
```
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

Perhaps::retry(function() {
    // Your logic here
}, 10, new ExponentialSequence(10, 100));
```
- **Exponential Growth**: Unlike linear or logarithmic sequences, this formula ensures that the numbers grow at an increasingly rapid pace. Each iteration introduces a massively larger value than the previous one.
- **Practical Usage**: Such sequences are most often used in scenarios where values need to expand exponentially, such as in backoff strategies or modeling growth over time.

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


# Example with Exception Handling

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
***
```php
Perhaps::retry(function () use ($data) {
    History::create($data); // example
}, 3, new LogarithmicSequence(1000000, 70));
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
