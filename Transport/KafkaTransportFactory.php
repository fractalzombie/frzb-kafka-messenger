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

use FRZB\Component\Messenger\Bridge\Kafka\Exception\ExtensionException;
use FRZB\Component\Messenger\Bridge\Kafka\Helper\ConfigHelper;
use Psr\Log\NullLogger;
use Symfony\Component\Messenger\Transport\Serialization\PhpSerializer;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;
use Symfony\Component\Messenger\Transport\TransportFactoryInterface;
use Symfony\Component\Messenger\Transport\TransportInterface;

/**
 * @author Mykhailo Shtanko <fractalzombie@gmail.com>
 */
class KafkaTransportFactory implements TransportFactoryInterface
{
    public function __construct(
        private KafkaLogger $logger,
        private bool $isDebug,
    ) {
    }

    public function createTransport(string $dsn, array $options, ?SerializerInterface $serializer = null): TransportInterface
    {
        $receiverConfig = ConfigHelper::createReceiverConfig($options);
        $senderConfig = ConfigHelper::createSenderConfig($options);
        $kafkaFactory = KafkaFactory::fromDsnAndOptions($dsn, $options);
        $connection = new Connection($kafkaFactory);
        $logger = $this->isDebug ? $this->logger : new KafkaLogger(new NullLogger());
        $serializer ??= new PhpSerializer();

        return new KafkaTransport($connection, $receiverConfig, $senderConfig, $serializer, $logger);
    }

    public function supports(string $dsn, array $options): bool
    {
        self::assertKafkaExtension();

        return str_starts_with($dsn, 'rdkafka://')
            || str_starts_with($dsn, 'rdkafka+ssl://')
            || str_starts_with($dsn, 'kafka://')
            || str_starts_with($dsn, 'kafka+ssl://');
    }

    private static function getKafkaLibraryVersion(): string
    {
        $major = (RD_KAFKA_VERSION & 0xFF000000) >> 24;
        $minor = (RD_KAFKA_VERSION & 0x00FF0000) >> 16;
        $patch = (RD_KAFKA_VERSION & 0x0000FF00) >> 8;

        return "{$major}.{$minor}.{$patch}";
    }

    private static function assertKafkaExtension(): void
    {
        match (true) {
            !\defined('RD_KAFKA_VERSION') => throw ExtensionException::constantNotDefined('RD_KAFKA_VERSION', 'ext-rdkafka'),
            version_compare(self::getKafkaLibraryVersion(), '1.0.0', '<') => throw ExtensionException::libraryVersionMismatch('librdkafka', '>=', '1.0.0'),
            !\extension_loaded('rdkafka') => throw ExtensionException::noExtension(self::class, 'ext-rdkafka'),
            default => null,
        };
    }
}
