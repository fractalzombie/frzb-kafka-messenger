<?php

declare(strict_types=1);

namespace FRZB\Component\Messenger\Bridge\Kafka\Tests\Transport;

use FRZB\Component\Messenger\Bridge\Kafka\Tests\Helper\OptionsHelper;
use FRZB\Component\Messenger\Bridge\Kafka\Transport\KafkaFactory;
use PHPUnit\Framework\TestCase;

/**
 * @requires extension rdkafka
 *
 * @group kafka-messenger
 *
 * @internal
 */
class KafkaFactoryTest extends TestCase
{
    public function testFromDsnAndOptionsMethod(): void
    {
        $dsn = getenv('MESSENGER_KAFKA_DSN') ?: 'kafka://0.0.0.0:9092';
        $options = OptionsHelper::getOptions();
        $factory = KafkaFactory::fromDsnAndOptions($dsn, $options);

        self::assertNotNull($factory);
        self::assertNotNull($factory->createProducer());
        self::assertNotNull($factory->createConsumer());
    }
}
