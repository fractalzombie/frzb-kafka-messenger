<?php

declare(strict_types=1);

namespace FRZB\Component\Messenger\Bridge\Kafka\Tests\Transport;

use FRZB\Component\Messenger\Bridge\Kafka\Exception\ConnectionException;
use FRZB\Component\Messenger\Bridge\Kafka\Exception\TransportException;
use FRZB\Component\Messenger\Bridge\Kafka\Tests\Fixtures\KafkaMessage;
use FRZB\Component\Messenger\Bridge\Kafka\Tests\Helper\MessageHelper;
use FRZB\Component\Messenger\Bridge\Kafka\Transport\Connection;
use FRZB\Component\Messenger\Bridge\Kafka\Transport\KafkaLogger;
use FRZB\Component\Messenger\Bridge\Kafka\Transport\KafkaSender;
use FRZB\Component\Messenger\Bridge\Kafka\Transport\KafkaSenderConfiguration;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface as Serializer;
use Symfony\Component\Uid\Uuid;

/**
 * @requires extension rdkafka
 *
 * @group kafka-messenger
 *
 * @internal
 */
class KafkaSenderTest extends TestCase
{
    private KafkaSenderConfiguration $configuration;
    private Connection $connection;
    private Serializer $serializer;
    private KafkaLogger $logger;
    private KafkaSender $sender;

    protected function setUp(): void
    {
        $this->configuration = new KafkaSenderConfiguration('test_topic', 10000, 3);
        $this->connection = $this->createMock(Connection::class);
        $this->serializer = $this->createMock(Serializer::class);
        $this->logger = $this->createMock(KafkaLogger::class);
        $this->sender = new KafkaSender($this->connection, $this->configuration, $this->serializer, $this->logger);
    }

    /** @dataProvider sendMethodProvider */
    public function testSendMethod(
        Envelope $envelope,
        array $payload,
        ?bool $isThrows = false,
        ?\Throwable $sendException = null,
    ): void {
        $this->serializer
            ->expects(self::once())
            ->method('encode')
            ->willReturn($payload)
        ;

        $sendMethod = $this->connection
            ->expects(self::once())
            ->method('send')
        ;

        if ($isThrows) {
            $sendMethod->willThrowException($sendException);
        }

        $this->logger
            ->expects(self::once())
            ->method($isThrows ? 'logError' : 'logProduce')
        ;

        if ($isThrows) {
            $this->expectException(TransportException::class);
        }

        $this->sender->send($envelope);
    }

    public function sendMethodProvider(): iterable
    {
        $body = json_encode(['id' => (string) Uuid::v4()], JSON_THROW_ON_ERROR);
        $kMessage = new KafkaMessage($body);
        $kStamp = MessageHelper::createKafkaStamp($body);
        $kReceivedStamp = MessageHelper::createKafkaReceivedStamp($kStamp);
        $envelope = MessageHelper::createEnvelope($kMessage, [$kReceivedStamp]);

        yield 'success' => [
            'envelope' => $envelope,
            'payload' => ['body' => $body, 'headers' => ['type' => $kMessage::class]],
            'is_throws' => false,
            'send_exception' => null,
        ];

        yield sprintf('failure with %s', ConnectionException::class) => [
            'envelope' => $envelope,
            'payload' => ['body' => $body, 'headers' => ['type' => $kMessage::class]],
            'is_throws' => true,
            'send_exception' => new ConnectionException(),
        ];

        yield sprintf('failure with %s', \JsonException::class) => [
            'envelope' => $envelope,
            'payload' => ['body' => $body, 'headers' => ['type' => $kMessage::class]],
            'is_throws' => true,
            'send_exception' => new \JsonException(),
        ];
    }
}
