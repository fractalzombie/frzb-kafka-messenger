<?php

declare(strict_types=1);

namespace FRZB\Component\Messenger\Bridge\Kafka\Tests\Transport;

use FRZB\Component\Messenger\Bridge\Kafka\Tests\Helper\OptionsHelper;
use FRZB\Component\Messenger\Bridge\Kafka\Transport\KafkaLogger;
use FRZB\Component\Messenger\Bridge\Kafka\Transport\KafkaTransportFactory;
use PHPUnit\Framework\TestCase;
use Symfony\Component\ErrorHandler\BufferingLogger;

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
        $dsn = 'kafka://kafka:9092';
        $options = OptionsHelper::getOptions();

        $factory = new KafkaTransportFactory(new KafkaLogger(new BufferingLogger()), false);

        self::assertTrue($factory->supports($dsn, $options));
        self::assertNotNull($factory->createTransport($dsn, $options));
    }
}
