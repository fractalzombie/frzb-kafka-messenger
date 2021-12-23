<?php

declare(strict_types=1);

namespace FRZB\Component\Messenger\Bridge\Kafka\Tests\Transport;

use Fp\Collections\ArrayList;
use FRZB\Component\Messenger\Bridge\Kafka\Helper\ConfigHelper;
use FRZB\Component\Messenger\Bridge\Kafka\Tests\Fixtures\KafkaMessage;
use FRZB\Component\Messenger\Bridge\Kafka\Tests\Helper\MessageHelper;
use FRZB\Component\Messenger\Bridge\Kafka\Tests\Helper\OptionsHelper;
use FRZB\Component\Messenger\Bridge\Kafka\Transport\Connection;
use FRZB\Component\Messenger\Bridge\Kafka\Transport\KafkaLogger;
use FRZB\Component\Messenger\Bridge\Kafka\Transport\KafkaReceivedStamp;
use FRZB\Component\Messenger\Bridge\Kafka\Transport\KafkaReceiverConfiguration;
use FRZB\Component\Messenger\Bridge\Kafka\Transport\KafkaSenderConfiguration;
use FRZB\Component\Messenger\Bridge\Kafka\Transport\KafkaTransport;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;
use Symfony\Component\Uid\Uuid;

/**
 * @requires extension rdkafka
 *
 * @group kafka-messenger
 *
 * @internal
 */
class KafkaTransportTest extends TestCase
{
    private array $options;
    private Connection $connection;
    private KafkaSenderConfiguration $senderConfiguration;
    private KafkaReceiverConfiguration $receiverConfiguration;
    private SerializerInterface $serializer;
    private KafkaLogger $logger;
    private KafkaTransport $transport;

    protected function setUp(): void
    {
        $this->options = OptionsHelper::getOptions(isRejectable: true);
        $this->connection = $this->createMock(Connection::class);
        $this->senderConfiguration = ConfigHelper::createSenderConfig($this->options);
        $this->receiverConfiguration = ConfigHelper::createReceiverConfig($this->options);
        $this->serializer = $this->createMock(SerializerInterface::class);
        $this->logger = $this->createMock(KafkaLogger::class);
        $this->transport = new KafkaTransport($this->connection, $this->receiverConfiguration, $this->senderConfiguration, $this->serializer, $this->logger);
    }

    /** @throws \JsonException */
    public function testGetMethod(): void
    {
        $body = json_encode(['id' => (string) Uuid::v4()], JSON_THROW_ON_ERROR);
        $kMessage = new KafkaMessage($body);
        $kStamp = MessageHelper::createKafkaStamp($body);
        $kReceivedStamp = MessageHelper::createKafkaReceivedStamp($kStamp);

        $this->connection
            ->expects(self::once())
            ->method('get')
            ->willReturn($kStamp)
        ;

        $this->logger
            ->expects(self::once())
            ->method('logReceive')
        ;

        $this->serializer
            ->expects(self::once())
            ->method('decode')
            ->willReturn(MessageHelper::createEnvelope($kMessage, [$kReceivedStamp]))
        ;

        /** @var ArrayList<Envelope> $envelopes */
        $envelopes = ArrayList::collect($this->transport->get());

        self::assertSame($kReceivedStamp->getMessage(), $envelopes->firstElement()->get()->last(KafkaReceivedStamp::class)?->getMessage());
        self::assertSame($kReceivedStamp->getTopicName(), $envelopes->firstElement()->get()->last(KafkaReceivedStamp::class)?->getTopicName());
    }

    /** @throws \JsonException */
    public function testAckMethod(): void
    {
        $body = json_encode(['id' => (string) Uuid::v4()], JSON_THROW_ON_ERROR);
        $kMessage = new KafkaMessage($body);
        $kStamp = MessageHelper::createKafkaStamp($body);
        $kReceivedStamp = MessageHelper::createKafkaReceivedStamp($kStamp);
        $envelope = MessageHelper::createEnvelope($kMessage, [$kReceivedStamp]);

        $this->connection
            ->expects(self::once())
            ->method('ack')
        ;

        $this->logger
            ->expects(self::once())
            ->method('logAcknowledge')
        ;

        $this->transport->ack($envelope);
    }

    /** @throws \JsonException */
    public function testRejectMethod(): void
    {
        $body = json_encode(['id' => (string) Uuid::v4()], JSON_THROW_ON_ERROR);
        $kMessage = new KafkaMessage($body);
        $kStamp = MessageHelper::createKafkaStamp($body);
        $kReceivedStamp = MessageHelper::createKafkaReceivedStamp($kStamp);
        $envelope = MessageHelper::createEnvelope($kMessage, [$kReceivedStamp]);

        $this->connection
            ->expects(self::once())
            ->method('ack')
        ;

        $this->connection
            ->expects(self::once())
            ->method('send')
        ;

        $this->logger
            ->expects(self::once())
            ->method('logAcknowledge')
        ;

        $this->transport->reject($envelope);
    }

    /** @throws \JsonException */
    public function testSendMethod(): void
    {
        $body = json_encode(['id' => (string) Uuid::v4()], JSON_THROW_ON_ERROR);
        $kMessage = new KafkaMessage($body);
        $kStamp = MessageHelper::createKafkaStamp($body);
        $kReceivedStamp = MessageHelper::createKafkaReceivedStamp($kStamp);
        $envelope = MessageHelper::createEnvelope($kMessage, [$kReceivedStamp]);

        $this->connection
            ->expects(self::once())
            ->method('send')
        ;

        $this->logger
            ->expects(self::once())
            ->method('logProduce')
        ;

        $this->transport->send($envelope);
    }
}
