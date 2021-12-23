<?php

declare(strict_types=1);

namespace FRZB\Component\Messenger\Bridge\Kafka\Tests\Helper;

use FRZB\Component\Messenger\Bridge\Kafka\Transport\KafkaReceivedStamp;
use FRZB\Component\Messenger\Bridge\Kafka\Transport\KafkaStamp;
use RdKafka\Message;
use Symfony\Component\Messenger\Envelope;

final class MessageHelper
{
    public const TEST_TOPIC = 'test_topic';

    public static function createKafkaMessage(
        string $payload,
        array $headers = [],
        string $topicName = self::TEST_TOPIC,
        int $error = RD_KAFKA_RESP_ERR_NO_ERROR,
        int $partition = RD_KAFKA_PARTITION_UA,
        int $offset = RD_KAFKA_OFFSET_BEGINNING,
        ?string $key = null,
    ): Message {
        $message = new Message();
        $message->payload = $payload;
        $message->headers = $headers;
        $message->topic_name = $topicName;
        $message->err = $error;
        $message->partition = $partition;
        $message->offset = $offset;
        $message->key = $key;
        $message->timestamp = (new \DateTimeImmutable())->getTimestamp();
        $message->len = \strlen($message->payload);

        return $message;
    }

    public static function createKafkaStamp(
        string $payload,
        array $headers = [],
        ?string $topicName = self::TEST_TOPIC,
        ?int $status = RD_KAFKA_RESP_ERR_NO_ERROR,
        int $offset = RD_KAFKA_OFFSET_BEGINNING,
        int $partition = RD_KAFKA_PARTITION_UA,
        ?string $key = null,
        bool $isRedelivered = false,
    ): KafkaStamp {
        return KafkaStamp::fromMessage(
            self::createKafkaMessage($payload, $headers, $topicName, $status, $partition, $offset, $key),
            $isRedelivered
        );
    }

    public static function createKafkaReceivedStamp(KafkaStamp $message, string $topicName = self::TEST_TOPIC): KafkaReceivedStamp
    {
        return new KafkaReceivedStamp($message, $topicName);
    }

    public static function createEnvelope(object $message, array $headers = []): Envelope
    {
        return new Envelope($message, $headers);
    }
}
