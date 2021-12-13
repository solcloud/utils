<?php

declare(strict_types=1);

namespace Solcloud\Utils;

use Redis;
use App\Worker;
use Dibi\Connection;
use Pimple\Container;
use Solcloud\Utils\HashMap;
use Psr\Log\LoggerInterface;
use Solcloud\Utils\DibiFactory;
use Solcloud\Utils\DateFactory;
use Solcloud\Consumer\QueueConfig;
use PhpAmqpLib\Channel\AMQPChannel;
use Pimple\ServiceProviderInterface;
use Solcloud\Consumer\QueueConnectionFactory;
use PhpAmqpLib\Connection\AbstractConnection;
use ElasticsearchUtils\Elasticsearch\Elasticsearch;
use PhpAmqpLib\Connection\Heartbeat\PCNTLHeartbeatSender;
use Aws\S3\S3Client;

class SolcloudProvider implements ServiceProviderInterface
{

    public function register(Container $container)
    {

        $container['dateFactory'] = function ($c): DateFactory {
            return new DateFactory;
        };

        $container['queueConfig'] = function ($c): QueueConfig {
            $queueConfig = new QueueConfig;

            if (HashMap::has('mq.connection.host')) {
                $queueConfig->setHost(HashMap::get('mq.connection.host'));
            }
            if (HashMap::has('mq.connection.port')) {
                $queueConfig->setPort(HashMap::get('mq.connection.port'));
            }
            if (HashMap::has('mq.connection.username')) {
                $queueConfig->setUsername(HashMap::get('mq.connection.username'));
            }
            if (HashMap::has('mq.connection.password')) {
                $queueConfig->setPassword(HashMap::get('mq.connection.password'));
            }
            if (HashMap::has('mq.connection.vhost')) {
                $queueConfig->setVhost(HashMap::get('mq.connection.vhost'));
            }
            if (HashMap::has('mq.connection.timeoutSec')) {
                $queueConfig->setConnectionTimeoutSec(HashMap::get('mq.connection.timeoutSec'));
            }
            if (HashMap::has('mq.connection.readWriteTimeoutSec')) {
                $queueConfig->setReadWriteTimeoutSec(HashMap::get('mq.connection.readWriteTimeoutSec'));
            }
            if (HashMap::has('mq.connection.keepalive')) {
                $queueConfig->setKeepalive(HashMap::get('mq.connection.keepalive'));
            }
            if (HashMap::has('mq.connection.heartbeatSec')) {
                $queueConfig->setHeartbeatSec(HashMap::get('mq.connection.heartbeatSec'));
            }

            return $queueConfig;
        };

        $container['queueConnectionFactory'] = function ($c): QueueConnectionFactory {
            return new QueueConnectionFactory($c['queueConfig']);
        };

        $container['queueConnection'] = $container->factory(function ($c): AbstractConnection {
            /* @var $factory QueueConnectionFactory */
            $factory = $c['queueConnectionFactory'];

            $lazy = HashMap::get('queueConnection.connection.lazy', false);
            $connectionType = HashMap::get('queueConnection.connection.type', 'stream');
            if ($connectionType === 'stream') {
                $connection = $factory->createStreamConnection($lazy);
            } elseif ($connectionType === 'socket') {
                $connection = $factory->createSocketConnection($lazy);
            }
            $connection->set_close_on_destruct(TRUE);

            return $connection;
        });

        $container['workerMainChannel'] = function ($c): AMQPChannel {
            $queueConnection = $c['queueConnection'];

            if (HashMap::get('workerMainChannel.pcntlHearbeatSenderEnable', false)) {
                (new PCNTLHeartbeatSender($queueConnection))->register();
            }

            return $queueConnection->channel();
        };

        $container['dibiFactory'] = function ($c): DibiFactory {
            $params = [
                'driver' => HashMap::get('dependency.dibi.driver', 'mysqli'),
                'host' => HashMap::get('dependency.dibi.host'),
                'username' => HashMap::get('dependency.dibi.username'),
                'password' => HashMap::get('dependency.dibi.password'),
                'lazy' => HashMap::get('dependency.dibi.lazy', true),
                'charset' => HashMap::get('dependency.dibi.charset', 'utf8'),
            ];
            if (HashMap::has('dependency.dibi.database')) {
                $params['database'] = HashMap::get('dependency.dibi.database');
            }

            return new DibiFactory($params);
        };

        $container['dibi'] = function ($c): Connection {
            return $c['dibiFactory']->create();
        };

        $container['redis'] = function ($c): Redis {
            $redis = new Redis();
            $redis->pconnect(HashMap::get('dependency.redis.host'));
            $redis->select(HashMap::get('dependency.redis.db'));

            return $redis;
        };

        $container['elasticsearch'] = function ($c): Elasticsearch {
            $elasticsearch = new Elasticsearch(
                    HashMap::get('elastisearch.connection.host'),
                    HashMap::get('elastisearch.connection.username'),
                    HashMap::get('elastisearch.connection.password'),
                    HashMap::get('elastisearch.connection.caFile')
            );

            return $elasticsearch;
        };

        $container['s3Client'] = function ($c): S3Client {
            $s3Client = new S3Client([
                'region' => HashMap::get('s3Client.connection.region', 'us-east-1'),
                'version' => HashMap::get('s3Client.connection.version', 'latest'),
                'endpoint' => HashMap::get('s3Client.connection.endpoint'),
                'use_path_style_endpoint' => HashMap::get('s3Client.connection.pathStyle', true),
                'credentials' => [
                    'key' => HashMap::get('s3Client.connection.credentials.key'),
                    'secret' => HashMap::get('s3Client.connection.credentials.secret'),
                ]
            ]);

            return $s3Client;
        };

        $container[LoggerInterface::class] = function ($c): LoggerInterface {
            return new PsrLogger();
        };

        $container['worker'] = function ($c): Worker {
            $worker = new Worker($c['workerMainChannel']);

            if (method_exists($worker, 'setDibi')) {
                $worker->setDibi($c['dibi']);
            }
            if (method_exists($worker, 'setLogger')) {
                $worker->setLogger($c[LoggerInterface::class]);
            }
            if (method_exists($worker, 'setRedis')) {
                $worker->setRedis($c['redis']);
            }

            $worker->setFailedExchange(HashMap::get('consumer.failedExchange', null, false));
            $worker->setFailedRoutingKey(HashMap::get('consumer.failedRoutingKey', null, false));
            $worker->setMaximumNumberOfProcessedMessages(HashMap::get('consumer.maximumNumberOfProcessedMessages'));
            $worker->setPrefetch(HashMap::get('consumer.prefetch.count', 1), HashMap::get('consumer.prefetch.sizeOctets', 0));

            return $worker;
        };

    }

}
