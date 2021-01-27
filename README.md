# Work Queue
Simple work queue using Beanstalk

## Installation
    $ composer require visual-craft/work-queue

## Usage
Create the queue manager
```PHP
use Pheanstalk\Pheanstalk;
use VisualCraft\WorkQueue\Logger;
use VisualCraft\WorkQueue\QueueManager;

$manager = new QueueManager(
    Pheanstalk::create('127.0.0.1', 11300),
    'some_queue',
    new Logger(null)
);
```

Setup queue processor and worker
```PHP
use VisualCraft\WorkQueue\QueueProcessor;
use VisualCraft\WorkQueue\Worker\JobMetadata;
use VisualCraft\WorkQueue\Worker\WorkerInterface;

class SomeWorker implements WorkerInterface
{
    public function work($payload, JobMetadata $metadata): void
    {
        // Process job
    }
}

// Create the queue processor and provide it with the worker
$processor = new QueueProcessor(
    $manager,
    new SomeWorker(),
);

// Process the queue
while($processor->process()) {}
```

Setup job adder and add the job
```PHP
$adder = new JobAdder($manager);
$id = $adder->add('some data');
```

## License
MIT
