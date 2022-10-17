<?php

declare(strict_types=1);

namespace FRZB\Component\Messenger\Bridge\Kafka\Tests\Fixtures\Serializer;

use FRZB\Component\Messenger\Bridge\Kafka\Integration\MessageSerializer;
use FRZB\Component\Messenger\Bridge\Kafka\Tests\Fixtures\Message\KafkaMessage;

final class KafkaMessageSerializer extends MessageSerializer
{
    protected static function getMessageType(): string
    {
        return KafkaMessage::class;
    }
}
