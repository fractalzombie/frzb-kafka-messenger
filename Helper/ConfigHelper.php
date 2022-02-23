<?php

declare(strict_types=1);

namespace FRZB\Component\Messenger\Bridge\Kafka\Helper;

use FRZB\Component\Messenger\Bridge\Kafka\Transport\KafkaReceiverConfiguration;
use FRZB\Component\Messenger\Bridge\Kafka\Transport\KafkaSenderConfiguration;
use JetBrains\PhpStorm\Pure;

final class ConfigHelper
{
    private const KEY_COMMIT_ASYNC = 'commit_async';
    private const KEY_RECEIVE_TIMEOUT = 'receive_timeout';
    private const KEY_FLUSH_TIMEOUT = 'flush_timeout';
    private const KEY_FLUSH_RETRIES = 'flush_retries';
    private const KEY_IS_REJECTABLE = 'is_rejectable';
    private const KEY_TOPIC = 'topic';
    private const KEY_TOPIC_NAME = 'name';

    #[Pure]
    public static function createReceiverConfig(array $options): KafkaReceiverConfiguration
    {
        return new KafkaReceiverConfiguration(
            $options[self::KEY_TOPIC][self::KEY_TOPIC_NAME],
            $options[self::KEY_RECEIVE_TIMEOUT] ?? 10000,
            $options[self::KEY_COMMIT_ASYNC] ?? false,
            $options[self::KEY_IS_REJECTABLE] ?? false,
        );
    }

    #[Pure]
    public static function createSenderConfig(array $options): KafkaSenderConfiguration
    {
        return new KafkaSenderConfiguration(
            $options[self::KEY_TOPIC][self::KEY_TOPIC_NAME],
            $options[self::KEY_FLUSH_TIMEOUT] ?? 10000,
            $options[self::KEY_FLUSH_RETRIES] ?? 3,
        );
    }
}
