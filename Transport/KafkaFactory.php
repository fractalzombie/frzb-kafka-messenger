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

    public function __construct(
        private readonly KafkaConfiguration $configuration,
    ) {
    }

    public static function fromDsnAndOptions(string $dsn, array $options): self
    {
        $kafka = $options[self::OPTIONS_KAFKA_KEY] ?? [];
        $topic = $options[self::OPTIONS_TOPIC_KEY] ?? [];

        return new self(self::getConfig($dsn, $kafka, $topic));
    }

    public function createConsumer(?KafkaConfiguration $configuration = null): KafkaConsumer
    {
        return new KafkaConsumer($configuration ?? $this->configuration);
    }

    public function createProducer(?KafkaConfiguration $configuration = null): KafkaProducer
    {
        return new KafkaProducer($configuration ?? $this->configuration);
    }

    private static function getConfig(string $dsn, array $kafka, array $topic): KafkaConfiguration
    {
        $config = new KafkaConfiguration();
        $config->set('metadata.broker.list', self::getBrokers($dsn));

        foreach (array_merge($kafka, $topic) as $option => $value) {
            $config->set($option, $value);
        }

        return $config;
    }

    private static function getBrokers(string $dsn): string
    {
        return implode(',', array_map(static fn (string $broker) => str_replace(self::DSN_PROTOCOLS, '', $broker), explode(',', $dsn)));
    }
}
