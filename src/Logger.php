<?php

declare(strict_types=1);

namespace VisualCraft\WorkQueue;

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

class Logger
{
    private ?LoggerInterface $logger;

    private array $commonContext;

    public function __construct(?LoggerInterface $logger, array $commonContext = [])
    {
        $this->logger = $logger;
        $this->commonContext = $commonContext;
    }

    public function withContext(array $commonContext): self
    {
        return new self($this->logger, array_replace($this->commonContext, $commonContext));
    }

    public function getLogger(): ?LoggerInterface
    {
        return $this->logger;
    }

    public function getCommonContext(): array
    {
        return $this->commonContext;
    }

    public function log($level, string $message, array $context = []): void
    {
        if ($this->logger !== null) {
            $this->logger->log($level, $message, array_replace($this->commonContext, $context));
        }
    }

    public function logException(string $message, \Exception $exception, array $context = [], $level = LogLevel::ERROR): void
    {
        if ($this->logger !== null) {
            $this->log($level, $message, $context);
            $exceptionContext = [
                'exception' => $this->buildExceptionMessage($exception),
            ];
            $index = 1;
            $previousException = $exception;

            while (($previousException = $previousException->getPrevious()) !== null) {
                $exceptionContext['previous_exception' . $index] = $this->buildExceptionMessage($previousException);
                $index++;
            }

            $this->log($level, 'Exception occurred', $exceptionContext);
        }
    }

    private function buildExceptionMessage(\Exception $e): string
    {
        return sprintf("class '%s', message '%s'", \get_class($e), $e->getMessage());
    }
}
