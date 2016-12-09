<?php

use Solcloud\Utils\HashMap;

$dir = realpath('./');

require $dir . '/vendor/autoload.php';
require $dir . '/_config/config.php';

$container = require $dir . '/container.php';

$version = HashMap::get('worker.version', 1);
require $dir . "/vendor/solcloud/utils/worker/workerInitV{$version}.php";
require $dir . "/vendor/solcloud/utils/worker/workerRunV{$version}.php";
