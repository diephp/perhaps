<?php

namespace DiePHP\Perhaps\Services;

use DiePHP\Perhaps\Exceptions\PerhapsException;
use Psr\Log\LoggerInterface;
use Traversable;

class PerhapsService
{

    /**
     * @var \Psr\Log\LoggerInterface|null
     */
    private ?LoggerInterface $logger            = null;

    private array            $excludeExceptions = [];

    private string           $errorLogType = 'warning';

    /**
     * Constructor method for the class.
     * @param LoggerInterface|null $logger            The logger instance for logging errors. Pass null to disable logging.
     * @param string               $errorLogType      The type of error logging to be performed. Default value is 'warning'.
     * @param array                $excludeExceptions The list of exceptions to be excluded from error logging. Default value is an empty array.
     * @return void
     */
    public function __construct(
        LoggerInterface $logger = null,
        string $errorLogType = 'warning',
        array           $excludeExceptions = []
    )
    {
        $this->logger = $logger;
        $this->excludeExceptions = $excludeExceptions;
        $this->errorLogType = $errorLogType;
    }

    /**
     * Retries a given function a specified number of times with a delay between each retry.
     * @param callable         $function      The function to retry.
     * @param int              $trys          The number of times to retry the function. Default is 2.
     * @param Traversable|null $delaySequence The sequence of delays between each retry. Default is null.
     * @return mixed The result of the function call if successful.
     * @throws \Exception When an exception occurs and is not excluded.
     * @throws \Exception When all retries fail.
     */
    public function retry(callable $function, int $trys = 2, Traversable $delaySequence = null)
    {
        $delay = intval($delaySequence ? $delaySequence->current() : 0);

        for ($iter = 1; $iter <= $trys; $iter++) {
            try {
                return \call_user_func($function, $iter);
            } catch (\Exception $exception) {
                if (\in_array(\get_class($exception), $this->excludeExceptions)) {
                    throw $exception;
                }

                if ($this->logger) {
                    $this->logger->{$this->errorLogType}("Perhaps::retry `$iter`/`$trys` catch: ".$exception->getMessage(), \array_filter([
                        'delay'    => $delay,
                        'sequence' => is_object($delaySequence)
                            ? \basename(\str_replace("\\", "/", \get_class($delaySequence)))
                            : null,
                        'previous' => $exception->getPrevious(),
                    ]));
                }

                \usleep($delay);

                if ($delaySequence) {
                    $delaySequence->next();
                    $delay = $delaySequence->current();
                }

            }
        }

        if (isset($exception)) {
            throw $exception;
        }

        throw new PerhapsException("Perhaps::retry have no success result");
    }

}
