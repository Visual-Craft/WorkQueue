<?php

namespace Examples;

require_once __DIR__ . '/../vendor/autoload.php';


$manager = require __DIR__ . '/queue-manager.php';
$manager->clear();
