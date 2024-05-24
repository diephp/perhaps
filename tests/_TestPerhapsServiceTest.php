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

}
