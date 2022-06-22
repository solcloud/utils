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
use ElasticsearchUtils\QueryBuilder;
use Pimple\ServiceProviderInterface;
use AmqpMessageQueue\AmqpMessageQueue;
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

            $queueConfig->setHost(HashMap::get('mq.connection.host', 'solcloud_rabbitmq'));
            if (HashMap::has('mq.connection.port')) {
                $queueConfig->setPort(HashMap::get('mq.connection.port'));
            }
            $queueConfig->setUsername(HashMap::get('mq.connection.username', 'dev'));
            $queueConfig->setPassword(HashMap::get('mq.connection.password', 'dev'));
            $queueConfig->setVhost(HashMap::get('mq.connection.vhost', '/'));
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

            if (HashMap::get('workerMainChannel.pcntlHeartbeatSenderEnable', false)) {
                (new PCNTLHeartbeatSender($queueConnection))->register();
            }

            return $queueConnection->channel();
        };

        $container['dibiFactory'] = function ($c): DibiFactory {
            $params = [
                'driver' => HashMap::get('dependency.dibi.driver', 'mysqli'),
                'host' => HashMap::get('dependency.dibi.host', 'solcloud_mysql'),
                'username' => HashMap::get('dependency.dibi.username', 'root'),
                'password' => HashMap::get('dependency.dibi.password', 'dev'),
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
            $redis->pconnect(HashMap::get('dependency.redis.host', 'solcloud_redis'));
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

        $container['queryBuilder'] = function ($c): QueryBuilder {
            return new QueryBuilder();
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
            if (method_exists($worker, 'setElasticsearchUtils')) {
                $worker->setElasticsearchUtils($c['elasticsearch'], $c['queryBuilder'], isset($c['dictionaryModel']) ? $c['dictionaryModel'] : null);
            }
            if (method_exists($worker, 'setCephSolcloudSdk')) {
                $worker->setCephSolcloudSdk($c['s3Client']);
            }

            $worker->setFailedExchange(HashMap::get('consumer.failedExchange', null, false));
            $worker->setFailedRoutingKey(HashMap::get('consumer.failedRoutingKey', null, false));
            $worker->setMaximumNumberOfProcessedMessages(HashMap::get('consumer.maximumNumberOfProcessedMessages', 1));
            $worker->setPrefetch(HashMap::get('consumer.prefetch.count', 1));

            return $worker;
        };

        $container['amqpMessageQueue'] = function ($c): AmqpMessageQueue {
            $amq = new AmqpMessageQueue([
                'host' => HashMap::get('mq.connection.host', 'solcloud_rabbitmq'),
                'port' => HashMap::get('mq.connection.port', 5672),
                'username' => HashMap::get('mq.connection.username', 'dev'),
                'password' => HashMap::get('mq.connection.password', 'dev'),
                'vhost' => HashMap::get('mq.connection.vhost', '/'),
            ]);
            $amq->setPersistMsg(HashMap::get('mq.connection.shouldPersistMsg', true));

            return $amq;
        };
    }

}
