<?php

use PhpAmqpLib\Exception\AMQPTimeoutException;
use Solcloud\Consumer\Exceptions\NumberOfProcessedMessagesExceed;
use Solcloud\Utils\HashMap;
use Solcloud\Utils\Logger;

/* @var $worker \Solcloud\Consumer\BaseConsumer */
$worker = $container['worker'];
$worker->consume(HashMap::get('consumer.consumeQueue'), HashMap::get('consumer.noAck', false));

while ($worker->hasCallback()) {
    try {
        $worker->wait(mt_rand(HashMap::get('consumer.channel.wait.minSec', 3600), HashMap::get('consumer.channel.wait.maxSec', 4800)));
    } catch (Exception $ex) {
        if ($ex instanceof NumberOfProcessedMessagesExceed || $ex instanceof AMQPTimeoutException) {
            Logger::exception($ex, Logger::TRACE);
        } else {
            Logger::exception($ex);
        }

        break;
    }
}
$worker->closeChannel();
