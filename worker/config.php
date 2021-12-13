<?php

use Solcloud\Utils\HashMap;

HashMap::set('consumer.noAck', false);
HashMap::set('mq.connection.vhost', '/');
HashMap::set('consumer.consumeQueue', 'test');
HashMap::set('consumer.channel.wait.minSec', 300);
HashMap::set('consumer.channel.wait.maxSec', 300);
HashMap::set('consumer.maximumNumberOfProcessedMessages', 1);
