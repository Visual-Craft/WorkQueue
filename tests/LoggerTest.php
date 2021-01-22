<?php

declare(strict_types=1);

namespace Tests\VisualCraft\WorkQueue;

use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use VisualCraft\WorkQueue\Logger;

/**
 * @internal
 */
class LoggerTest extends TestCase
{
    public function testInitializeWithoutLogger(): void
    {
        $logger = new Logger(null);

        $this->assertNull($logger->getLogger());
        $this->assertSame([], $logger->getCommonContext());
    }

    public function testInitializeWithLogger(): void
    {
        $loggerImpl = $this->createMock(LoggerInterface::class);
        $logger = new Logger($loggerImpl);

        $this->assertSame($loggerImpl, $logger->getLogger());
    }

    public function testCommonContext(): void
    {
        $context = ['foo' => 1];
        $logger = new Logger(null, $context);

        $this->assertSame($context, $logger->getCommonContext());
    }

    public function testLog(): void
    {
        $level = LogLevel::DEBUG;
        $message = '__message__';
        $context = ['boo' => 2];
        $loggerImpl = $this->createMock(LoggerInterface::class);
        $logger = new Logger($loggerImpl);

        $loggerImpl->expects($this->once())
            ->method('log')
            ->with($this->equalTo($level), $this->equalTo($message), $this->equalTo($context))
        ;

        $logger->log($level, $message, $context);
    }

    public function testLogWithCommonContext(): void
    {
        $loggerImpl = $this->createMock(LoggerInterface::class);
        $logger = new Logger($loggerImpl, ['foo' => 1, 'foo1' => 4]);

        $loggerImpl->expects($this->once())
            ->method('log')
            ->with($this->anything(), $this->anything(), ['foo' => 3, 'foo1' => 4, 'boo' => 2])
        ;

        $logger->log(LogLevel::DEBUG, '__message__', ['boo' => 2, 'foo' => 3]);
    }

    public function testLogException(): void
    {
        $level = LogLevel::DEBUG;
        $message = '__message__';
        $context = ['boo' => 2];
        $prevException2 = new \RuntimeException('__exception2__');
        $prevException3 = new \Exception('__exception3__', 0, $prevException2);
        $exception = new \Exception('__exception1__', 0, $prevException3);
        $loggerImpl = $this->createMock(LoggerInterface::class);
        $logger = new Logger($loggerImpl);

        $loggerImpl->expects($this->exactly(2))
            ->method('log')
            ->withConsecutive(
                [$this->equalTo($level), $this->equalTo($message), $this->equalTo($context)],
                [$this->equalTo($level), $this->equalTo('Exception occurred'), $this->equalTo([
                    'exception' => "class 'Exception', message '__exception1__'",
                    'previous_exception1' => "class 'Exception', message '__exception3__'",
                    'previous_exception2' => "class 'RuntimeException', message '__exception2__'",
                ])]
            )
        ;

        $logger->logException($message, $exception, $context, $level);
    }
}
