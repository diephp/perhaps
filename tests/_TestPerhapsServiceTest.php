<?php

use DiePHP\Perhaps\Exceptions\PerhapsException;
use DiePHP\Perhaps\Services\PerhapsService;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class _TestPerhapsServiceTest extends TestCase
{

    /**
     * @var PerhapsService
     */
    private $perhapsService;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function setUp() : void
    {
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->perhapsService = new PerhapsService($this->logger);
    }

    public function testRetrySuccess() : void
    {
        $function = function () { return "success"; };

        $this->logger->expects($this->never())
            ->method('warning');

        $result = $this->perhapsService->retry($function);

        $this->assertEquals("success", $result);
    }

    public function testRetryFailure() : void
    {
        $function = function () { throw new PerhapsException(); };
        $this->logger->expects($this->any())
            ->method('warning');

        $this->expectException(PerhapsException::class);

        $result = $this->perhapsService->retry($function);
    }

    /**
     * The callback must be invoked exactly once when it does not throw.
     */
    public function testSuccessfulCallbackIsCalledOnlyOnce() : void
    {
        $calls = 0;

        $result = $this->perhapsService->retry(function () use (&$calls) {
            $calls++;

            return 'ok';
        }, 5);

        $this->assertSame('ok', $result);
        $this->assertSame(1, $calls);
    }

    /**
     * The callback receives the current attempt number, starting from 1.
     */
    public function testCallbackReceivesAttemptNumber() : void
    {
        $attempts = [];

        $result = $this->perhapsService->retry(function ($attempt) use (&$attempts) {
            $attempts[] = $attempt;

            if ($attempt < 3) {
                throw new RuntimeException('temporary failure');
            }

            return 'recovered on ' . $attempt;
        }, 5);

        $this->assertSame('recovered on 3', $result);
        $this->assertSame([1, 2, 3], $attempts);
    }

    /**
     * Retrying must stop as soon as the callback returns, even if attempts remain.
     */
    public function testRetryStopsAfterFirstSuccess() : void
    {
        $calls = 0;

        $this->logger->expects($this->once())
            ->method('warning');

        $this->perhapsService->retry(function () use (&$calls) {
            $calls++;

            if ($calls === 1) {
                throw new RuntimeException('first attempt fails');
            }

            return true;
        }, 10);

        $this->assertSame(2, $calls);
    }

    /**
     * A permanently failing callback is executed exactly $trys times.
     */
    public function testCallbackIsExecutedExactlyTrysTimes() : void
    {
        $calls = 0;

        try {
            $this->perhapsService->retry(function () use (&$calls) {
                $calls++;

                throw new RuntimeException('always fails');
            }, 4);
            $this->fail('An exception was expected after all attempts failed.');
        } catch (RuntimeException $exception) {
            $this->assertSame('always fails', $exception->getMessage());
        }

        $this->assertSame(4, $calls);
    }

    /**
     * The original exception of the last attempt must be re-thrown, not wrapped.
     */
    public function testLastExceptionIsRethrown() : void
    {
        $function = function ($attempt) {
            throw new LogicException('failure #' . $attempt);
        };

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('failure #3');

        $this->perhapsService->retry($function, 3);
    }

    /**
     * Excluded exceptions must be re-thrown immediately, without retrying or logging.
     */
    public function testExcludedExceptionIsThrownImmediately() : void
    {
        $service = new PerhapsService($this->logger, 'warning', [PerhapsException::class]);
        $calls = 0;

        $this->logger->expects($this->never())
            ->method('warning');

        try {
            $service->retry(function () use (&$calls) {
                $calls++;

                throw new PerhapsException('do not retry me');
            }, 5);
            $this->fail('The excluded exception was expected to be thrown.');
        } catch (PerhapsException $exception) {
            $this->assertSame('do not retry me', $exception->getMessage());
        }

        $this->assertSame(1, $calls);
    }

    /**
     * The exclusion list is matched by exact class name, so unlisted exceptions are still retried.
     */
    public function testNotExcludedExceptionIsRetried() : void
    {
        $service = new PerhapsService($this->logger, 'warning', [LogicException::class]);
        $calls = 0;

        try {
            $service->retry(function () use (&$calls) {
                $calls++;

                throw new RuntimeException('retry me');
            }, 3);
            $this->fail('An exception was expected after all attempts failed.');
        } catch (RuntimeException $exception) {
            $this->assertSame('retry me', $exception->getMessage());
        }

        $this->assertSame(3, $calls);
    }

    /**
     * Errors (\Throwable but not \Exception) are not caught and abort retrying at once.
     */
    public function testErrorIsNotCaught() : void
    {
        $calls = 0;

        $this->logger->expects($this->never())
            ->method('warning');

        try {
            $this->perhapsService->retry(function () use (&$calls) {
                $calls++;

                throw new Error('fatal error, not retryable');
            }, 5);
            $this->fail('The error was expected to be thrown.');
        } catch (Error $error) {
            $this->assertSame('fatal error, not retryable', $error->getMessage());
        }

        $this->assertSame(1, $calls);
    }

    /**
     * With zero attempts the callback is never called and a PerhapsException is thrown.
     */
    public function testZeroTrysThrowsPerhapsException() : void
    {
        $calls = 0;

        try {
            $this->perhapsService->retry(function () use (&$calls) {
                $calls++;

                return 'never reached';
            }, 0);
            $this->fail('A PerhapsException was expected.');
        } catch (PerhapsException $exception) {
            $this->assertSame('Perhaps::retry have no success result', $exception->getMessage());
        }

        $this->assertSame(0, $calls);
    }

    /**
     * The service must work without any logger.
     */
    public function testWorksWithoutLogger() : void
    {
        $service = new PerhapsService();

        $result = $service->retry(function ($attempt) {
            if ($attempt < 2) {
                throw new RuntimeException('temporary failure');
            }

            return 'ok';
        }, 3);

        $this->assertSame('ok', $result);
    }

    /**
     * Failed attempts are reported with the configured log level.
     */
    public function testCustomErrorLogTypeIsUsed() : void
    {
        $service = new PerhapsService($this->logger, 'error');

        $this->logger->expects($this->never())
            ->method('warning');

        $this->logger->expects($this->exactly(2))
            ->method('error');

        try {
            $service->retry(function () {
                throw new RuntimeException('always fails');
            }, 2);
        } catch (RuntimeException $exception) {
            $this->addToAssertionCount(1);
        }
    }

    /**
     * Every failed attempt is logged with the attempt counter and the exception message.
     */
    public function testFailedAttemptsAreLogged() : void
    {
        $messages = [];

        $this->logger->expects($this->exactly(2))
            ->method('warning')
            ->willReturnCallback(function ($message) use (&$messages) {
                $messages[] = (string) $message;
            });

        try {
            $this->perhapsService->retry(function () {
                throw new RuntimeException('service unavailable');
            }, 2);
        } catch (RuntimeException $exception) {
            $this->addToAssertionCount(1);
        }

        $this->assertCount(2, $messages);
        $this->assertStringContainsString('Perhaps::retry `1`/`2`', $messages[0]);
        $this->assertStringContainsString('Perhaps::retry `2`/`2`', $messages[1]);
        $this->assertStringContainsString('service unavailable', $messages[0]);
    }

    /**
     * The delay sequence is advanced once per failed attempt and reported in the log context.
     */
    public function testDelaySequenceIsAdvancedOnEveryFailedAttempt() : void
    {
        $contexts = [];

        $this->logger->expects($this->exactly(3))
            ->method('warning')
            ->willReturnCallback(function ($message, array $context = []) use (&$contexts) {
                $contexts[] = $context;
            });

        try {
            $this->perhapsService->retry(function () {
                throw new RuntimeException('always fails');
            }, 3, new ArrayIterator([100, 200, 300, 400]));
        } catch (RuntimeException $exception) {
            $this->addToAssertionCount(1);
        }

        $this->assertSame([100, 200, 300], array_column($contexts, 'delay'));
        $this->assertSame(['ArrayIterator', 'ArrayIterator', 'ArrayIterator'], array_column($contexts, 'sequence'));
    }

    /**
     * A sequence shorter than $trys must not emit a deprecation from usleep(null).
     */
    public function testExhaustedDelaySequenceIsCastToInt() : void
    {
        $contexts = [];

        $this->logger->expects($this->exactly(3))
            ->method('warning')
            ->willReturnCallback(function ($message, array $context = []) use (&$contexts) {
                $contexts[] = $context;
            });

        $previous = set_error_handler(function ($severity, $message) {
            $this->fail('Unexpected PHP notice while retrying: ' . $message);
        }, E_ALL);

        try {
            $this->perhapsService->retry(function () {
                throw new RuntimeException('always fails');
            }, 3, new ArrayIterator([100]));
            $this->fail('An exception was expected after all attempts failed.');
        } catch (RuntimeException $exception) {
            $this->addToAssertionCount(1);
        } finally {
            set_error_handler($previous);
        }

        $this->assertSame(100, $contexts[0]['delay']);
        $this->assertArrayNotHasKey('delay', $contexts[1]);
        $this->assertArrayNotHasKey('delay', $contexts[2]);
    }

    /**
     * A Traversable from the sequences package can be used as the delay source.
     */
    public function testRetryWithSequencesPackage() : void
    {
        $contexts = [];

        $this->logger->expects($this->exactly(2))
            ->method('warning')
            ->willReturnCallback(function ($message, array $context = []) use (&$contexts) {
                $contexts[] = $context;
            });

        $result = $this->perhapsService->retry(function ($attempt) {
            if ($attempt < 3) {
                throw new RuntimeException('temporary failure');
            }

            return 'ok';
        }, 5, new \DiePHP\Sequences\LinearSequence(10, 10));

        $this->assertSame('ok', $result);
        $this->assertSame(['LinearSequence', 'LinearSequence'], array_column($contexts, 'sequence'));
    }

    /**
     * Without a sequence no delay is reported, because empty context values are filtered out.
     */
    public function testNoDelayContextWithoutSequence() : void
    {
        $contexts = [];

        $this->logger->expects($this->once())
            ->method('warning')
            ->willReturnCallback(function ($message, array $context = []) use (&$contexts) {
                $contexts[] = $context;
            });

        try {
            $this->perhapsService->retry(function () {
                throw new RuntimeException('always fails');
            }, 1);
        } catch (RuntimeException $exception) {
            $this->addToAssertionCount(1);
        }

        $this->assertArrayNotHasKey('delay', $contexts[0]);
        $this->assertArrayNotHasKey('sequence', $contexts[0]);
    }

    /**
     * The previous exception is passed to the log context when it exists.
     */
    public function testPreviousExceptionIsLogged() : void
    {
        $previous = new LogicException('root cause');
        $contexts = [];

        $this->logger->expects($this->once())
            ->method('warning')
            ->willReturnCallback(function ($message, array $context = []) use (&$contexts) {
                $contexts[] = $context;
            });

        try {
            $this->perhapsService->retry(function () use ($previous) {
                throw new RuntimeException('wrapper', 0, $previous);
            }, 1);
        } catch (RuntimeException $exception) {
            $this->addToAssertionCount(1);
        }

        $this->assertSame($previous, $contexts[0]['previous']);
    }

}
