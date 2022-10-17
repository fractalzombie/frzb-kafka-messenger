<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Mykhailo Shtanko <fractalzombie@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FRZB\Component\Messenger\Bridge\Kafka\Transport;

use RdKafka\Conf as KafkaConfiguration;
use RdKafka\KafkaConsumer;
use RdKafka\Producer as KafkaProducer;

/**
 * Kafka factory that uses for consumer and producer creation.
 *
 * @author Mykhailo Shtanko <fractalzombie@gmail.com>
 */
class KafkaFactory
{
    private const DSN_PROTOCOLS = ['rdkafka://', 'rdkafka+ssl://', 'kafka://', 'kafka+ssl://'];
    private const OPTIONS_KAFKA_KEY = 'kafka_conf';
    private const OPTIONS_TOPIC_KEY = 'topic_conf';

    private const CONSUMER_PROPS = [
        'enable.auto.offset.store',
        'key.deserializer',
        'value.deserializer',
        'bootstrap.servers',
        'fetch.min.bytes',
        'group.id',
        'heartbeat.interval.ms',
        'max.partition.fetch.bytes',
        'session.timeout.ms',
        'ssl.key.password',
        'ssl.keystore.certificate.chain',
        'ssl.keystore.key',
        'ssl.keystore.location',
        'ssl.keystore.password',
        'ssl.truststore.certificates',
        'ssl.truststore.location',
        'ssl.truststore.password',
        'allow.auto.create.topics',
        'auto.offset.reset',
        'client.dns.lookup',
        'connections.max.idle.ms',
        'default.api.timeout.ms',
        'enable.auto.commit',
        'exclude.internal.topics',
        'fetch.max.bytes',
        'group.instance.id',
        'isolation.level',
        'max.poll.interval.ms',
        'max.poll.records',
        'partition.assignment.strategy',
        'receive.buffer.bytes',
        'request.timeout.ms',
        'sasl.client.callback.handler.class',
        'sasl.jaas.config',
        'sasl.kerberos.service.name',
        'sasl.login.callback.handler.class',
        'sasl.login.class',
        'sasl.mechanism',
        'sasl.oauthbearer.jwks.endpoint.url',
        'sasl.oauthbearer.token.endpoint.url',
        'security.protocol',
        'send.buffer.bytes',
        'socket.connection.setup.timeout.max.ms',
        'socket.connection.setup.timeout.ms',
        'ssl.enabled.protocols',
        'ssl.keystore.type',
        'ssl.protocol',
        'ssl.provider',
        'ssl.truststore.type',
        'auto.commit.interval.ms',
        'check.crcs',
        'client.id',
        'client.rack',
        'fetch.max.wait.ms',
        'interceptor.classes',
        'metadata.max.age.ms',
        'metric.reporters',
        'metrics.num.samples',
        'metrics.recording.level',
        'metrics.sample.window.ms',
        'reconnect.backoff.max.ms',
        'reconnect.backoff.ms',
        'retry.backoff.ms',
        'sasl.kerberos.kinit.cmd',
        'sasl.kerberos.min.time.before.relogin',
        'sasl.kerberos.ticket.renew.jitter',
        'sasl.kerberos.ticket.renew.window.factor',
        'sasl.login.connect.timeout.ms',
        'sasl.login.read.timeout.ms',
        'sasl.login.refresh.buffer.seconds',
        'sasl.login.refresh.min.period.seconds',
        'sasl.login.refresh.window.factor',
        'sasl.login.refresh.window.jitter',
        'sasl.login.retry.backoff.max.ms',
        'sasl.login.retry.backoff.ms',
        'sasl.oauthbearer.clock.skew.seconds',
        'sasl.oauthbearer.expected.audience',
        'sasl.oauthbearer.expected.issuer',
        'sasl.oauthbearer.jwks.endpoint.refresh.ms',
        'sasl.oauthbearer.jwks.endpoint.retry.backoff.max.ms',
        'sasl.oauthbearer.jwks.endpoint.retry.backoff.ms',
        'sasl.oauthbearer.scope.claim.name',
        'sasl.oauthbearer.sub.claim.name',
        'security.providers',
        'ssl.cipher.suites',
        'ssl.endpoint.identification.algorithm',
        'ssl.engine.factory.class',
        'ssl.keymanager.algorithm',
        'ssl.secure.random.implementation',
        'ssl.trustmanager.algorithm',
    ];

    private const PRODUCER_PROPS = [
        'key.serializer',
        'value.serializer',
        'bootstrap.servers',
        'buffer.memory',
        'compression.type',
        'retries',
        'ssl.key.password',
        'ssl.keystore.certificate.chain',
        'ssl.keystore.key',
        'ssl.keystore.location',
        'ssl.keystore.password',
        'ssl.truststore.certificates',
        'ssl.truststore.location',
        'ssl.truststore.password',
        'batch.size',
        'client.dns.lookup',
        'client.id',
        'connections.max.idle.ms',
        'delivery.timeout.ms',
        'linger.ms',
        'max.block.ms',
        'max.request.size',
        'partitioner.class',
        'receive.buffer.bytes',
        'request.timeout.ms',
        'sasl.client.callback.handler.class',
        'sasl.jaas.config',
        'sasl.kerberos.service.name',
        'sasl.login.callback.handler.class',
        'sasl.login.class',
        'sasl.mechanism',
        'sasl.oauthbearer.jwks.endpoint.url',
        'sasl.oauthbearer.token.endpoint.url',
        'security.protocol',
        'send.buffer.bytes',
        'socket.connection.setup.timeout.max.ms',
        'socket.connection.setup.timeout.ms',
        'ssl.enabled.protocols',
        'ssl.keystore.type',
        'ssl.protocol',
        'ssl.provider',
        'ssl.truststore.type',
        'acks',
        'enable.idempotence',
        'interceptor.classes',
        'max.in.flight.requests.per.connection',
        'metadata.max.age.ms',
        'metadata.max.idle.ms',
        'metric.reporters',
        'metrics.num.samples',
        'metrics.recording.level',
        'metrics.sample.window.ms',
        'reconnect.backoff.max.ms',
        'reconnect.backoff.ms',
        'retry.backoff.ms',
        'sasl.kerberos.kinit.cmd',
        'sasl.kerberos.min.time.before.relogin',
        'sasl.kerberos.ticket.renew.jitter',
        'sasl.kerberos.ticket.renew.window.factor',
        'sasl.login.connect.timeout.ms',
        'sasl.login.read.timeout.ms',
        'sasl.login.refresh.buffer.seconds',
        'sasl.login.refresh.min.period.seconds',
        'sasl.login.refresh.window.factor',
        'sasl.login.refresh.window.jitter',
        'sasl.login.retry.backoff.max.ms',
        'sasl.login.retry.backoff.ms',
        'sasl.oauthbearer.clock.skew.seconds',
        'sasl.oauthbearer.expected.audience',
        'sasl.oauthbearer.expected.issuer',
        'sasl.oauthbearer.jwks.endpoint.refresh.ms',
        'sasl.oauthbearer.jwks.endpoint.retry.backoff.max.ms',
        'sasl.oauthbearer.jwks.endpoint.retry.backoff.ms',
        'sasl.oauthbearer.scope.claim.name',
        'sasl.oauthbearer.sub.claim.name',
        'security.providers',
        'ssl.cipher.suites',
        'ssl.endpoint.identification.algorithm',
        'ssl.engine.factory.class',
        'ssl.keymanager.algorithm',
        'ssl.secure.random.implementation',
        'ssl.trustmanager.algorithm',
        'transaction.timeout.ms',
        'transactional.id',
    ];

    public function __construct(
        private readonly KafkaConfiguration $consumerConfiguration,
        private readonly KafkaConfiguration $producerConfiguration,
    ) {
    }

    public static function fromDsnAndOptions(string $dsn, array $options): self
    {
        $kafka = $options[self::OPTIONS_KAFKA_KEY] ?? [];
        $topic = $options[self::OPTIONS_TOPIC_KEY] ?? [];

        return new self(
            self::getConfig($dsn, $kafka, $topic, self::CONSUMER_PROPS),
            self::getConfig($dsn, $kafka, $topic, self::PRODUCER_PROPS),
        );
    }

    public function createConsumer(?KafkaConfiguration $configuration = null): KafkaConsumer
    {
        return new KafkaConsumer($configuration ?? $this->consumerConfiguration);
    }

    public function createProducer(?KafkaConfiguration $configuration = null): KafkaProducer
    {
        return new KafkaProducer($configuration ?? $this->producerConfiguration);
    }

    private static function getConfig(string $dsn, array $kafka, array $topic, array $existingOptions): KafkaConfiguration
    {
        $config = new KafkaConfiguration();
        $config->set('metadata.broker.list', self::getBrokers($dsn));

        foreach (array_merge($kafka, $topic) as $option => $value) {
            if (array_search($option, $existingOptions, true)) {
                $config->set($option, $value);
            }
        }

        return $config;
    }

    private static function getBrokers(string $dsn): string
    {
        return implode(',', array_map(static fn (string $broker) => str_replace(self::DSN_PROTOCOLS, '', $broker), explode(',', $dsn)));
    }
}
