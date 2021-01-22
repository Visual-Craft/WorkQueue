<?php

namespace Examples;

use Pheanstalk\Pheanstalk;
use VisualCraft\WorkQueue\Logger;
use Psr\Log\AbstractLogger;
use VisualCraft\WorkQueue\QueueManager;


// Options
$beanstalkdHost = '127.0.0.1';
$beanstalkdPort = 11300;
$queueName = 'some_queue';

// Create logger
$logger = new Logger(new class extends AbstractLogger
{
    public function log($level, $message, array $context = []): void
    {
        fprintf(
            STDERR,
            "[%s] :%s: %s %s\n",
            (new \DateTime())->format(\DateTime::RFC3339_EXTENDED),
            strtoupper($level),
            $message,
            json_encode($context, JSON_THROW_ON_ERROR)
        );
    }
});

// Create Pheanstalk
$connection = Pheanstalk::create($beanstalkdHost, $beanstalkdPort);

// Create the queue manager
return new QueueManager($connection, $queueName, $logger);
