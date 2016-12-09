<?php

use Solcloud\Utils\Logger;
use Solcloud\Utils\HashMap;

$dir = realpath('./');

require $dir . '/vendor/autoload.php';
require $dir . '/_config/config.php';

$container = require $dir . '/container.php';

HashMap::set('notify.mail.enable', false);

$version = HashMap::get('worker.version', 1);
require $dir . "/vendor/solcloud/utils/worker/workerInitV{$version}.php";

Logger::setDisplay(true);
Logger::setLog(null);
Logger::setRemoteLogUrl(null);
Logger::setLevel(Logger::TRACE);

require $dir . "/vendor/solcloud/utils/worker/workerRunV{$version}.php";
