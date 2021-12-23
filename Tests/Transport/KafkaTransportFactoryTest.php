<?php

declare(strict_types=1);

namespace FRZB\Component\Messenger\Bridge\Kafka\Tests\Transport;

use FRZB\Component\Messenger\Bridge\Kafka\Tests\Helper\OptionsHelper;
use FRZB\Component\Messenger\Bridge\Kafka\Transport\KafkaLogger;
use FRZB\Component\Messenger\Bridge\Kafka\Transport\KafkaTransportFactory;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

/**
 * @requires extension rdkafka
 *
 * @group kafka-messenger
 *
 * @internal
 */
class KafkaTransportFactoryTest extends TestCase
{
    public function testSupportsMethod(): void
    {
        $dsn = getenv('MESSENGER_KAFKA_DSN') ?: 'kafka://0.0.0.0:9092';
        $options = OptionsHelper::getOptions();

        $factory = new KafkaTransportFactory();

        self::assertTrue($factory->supports($dsn, $options));
        self::assertNotNull($factory->createTransport($dsn, $options));
    }
}
