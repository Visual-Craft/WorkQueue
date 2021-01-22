<?php

namespace Examples;

require_once __DIR__ . '/../vendor/autoload.php';

use VisualCraft\WorkQueue\JobAdder;


$manager = require __DIR__ . '/queue-manager.php';
$adder = new JobAdder($manager);

// Add the job
$id = $adder->add($argv[1] ?? 'some data');

echo "Job id: {$id}\n";
