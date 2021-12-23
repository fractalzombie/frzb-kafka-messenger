<?php

declare(strict_types=1);

namespace FRZB\Component\Messenger\Bridge\Kafka\Tests\Helper;

final class OptionsHelper
{
    public static function getOptions(
        bool $isCommitAsync = true,
        int $receiveTimeout = 10000,
        int $flushTimeout = 10000,
        int $flushRetries = 3,
        bool $isRejectable = false,
        string $topicName = 'test_topic',
    ): array {
        return [
            'commit_async' => $isCommitAsync,
            'receive_timeout' => $receiveTimeout,
            'flush_timeout' => $flushTimeout,
            'flush_retries' => $flushRetries,
            'is_rejectable' => $isRejectable,
            'topic' => ['name' => $topicName],
            'kafka_conf' => [
                'enable.auto.offset.store' => 'false',
                'group.id' => 'test_group_id',
                'enable.auto.commit' => 'true',
            ],
            'topic_conf' => [
                'auto.offset.reset' => 'earliest',
                'auto.commit.interval.ms' => '100',
            ],
        ];
    }
}
