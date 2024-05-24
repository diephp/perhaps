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

    public function __construct(
        LoggerInterface $logger = null,
        array           $excludeExceptions = []

    )
    {
        $this->logger = $logger;
        $this->excludeExceptions = $excludeExceptions;
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
                    $this->logger->warning("Perhaps::retry `$iter`/`$trys` catch: ".$exception->getMessage(), [
                        'delay'    => $delay,
                        'sequence' => $delaySequence ? basename(get_class($delaySequence)) : null,
                        'previous' => $exception->getPrevious(),
                    ]);
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
