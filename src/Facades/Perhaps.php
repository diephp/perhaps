<?php

namespace DiePHP\Perhaps\Facades;

/**
 * @method static retry(callable $function, int $trys = 2, Traversable $delaySequence = null)
 */
class Perhaps extends \Illuminate\Support\Facades\Facade
{

    /**
     * Get the registered name of the component.
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return Perhaps::class;
    }

}
