<?php

declare(strict_types=1);

namespace FRZB\Component\Messenger\Bridge\Kafka\Tests\Integration;

use FRZB\Component\Messenger\Bridge\Kafka\Integration\MessageSerializer;
use FRZB\Component\Messenger\Bridge\Kafka\Tests\Fixtures\KafkaMessage;
use FRZB\Component\Messenger\Bridge\Kafka\Tests\Helper\DependencyHelper;
use PHPUnit\Framework\TestCase;

/**
 * @group kafka-messenger
 *
 * @internal
 */
class KafkaMessageSerializerTest extends TestCase
{
    /** @throws \JsonException */
    public function testDecodeMethod(): void
    {
        $serializer = new KafkaMessageSerializer(DependencyHelper::getSymfonySerializer());

        $data = ['body' => json_encode(['message' => 'hello world!'], \JSON_THROW_ON_ERROR), 'headers' => ['type' => 'NoExistsMessage']];

        $envelope = $serializer->decode($data);

        self::assertInstanceOf(KafkaMessage::class, $envelope->getMessage());
    }
}

final class KafkaMessageSerializer extends MessageSerializer
{
    protected static function getMessageType(): string
    {
        return KafkaMessage::class;
    }
}
