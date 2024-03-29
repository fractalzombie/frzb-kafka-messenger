<?php

declare(strict_types=1);

namespace FRZB\Component\Messenger\Bridge\Kafka\Tests\Transport;

use Fp\Collections\ArrayList;
use FRZB\Component\Messenger\Bridge\Kafka\Helper\ConfigHelper;
use FRZB\Component\Messenger\Bridge\Kafka\Tests\Fixtures\Message\KafkaMessage;
use FRZB\Component\Messenger\Bridge\Kafka\Tests\Helper\MessageHelper;
use FRZB\Component\Messenger\Bridge\Kafka\Tests\Helper\OptionsHelper;
use FRZB\Component\Messenger\Bridge\Kafka\Transport\Connection;
use FRZB\Component\Messenger\Bridge\Kafka\Transport\KafkaReceivedStamp;
use FRZB\Component\Messenger\Bridge\Kafka\Transport\KafkaReceiverConfiguration;
use FRZB\Component\Messenger\Bridge\Kafka\Transport\KafkaSenderConfiguration;
use FRZB\Component\Messenger\Bridge\Kafka\Transport\KafkaTransport;
use PHPUnit\Framework\TestCase;
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
    private KafkaTransport $transport;

    protected function setUp(): void
    {
        $this->options = OptionsHelper::getOptions(isRejectable: true);
        $this->connection = $this->createMock(Connection::class);
        $this->senderConfiguration = ConfigHelper::createSenderConfig($this->options);
        $this->receiverConfiguration = ConfigHelper::createReceiverConfig($this->options);
        $this->serializer = $this->createMock(SerializerInterface::class);
        $this->transport = new KafkaTransport($this->connection, $this->receiverConfiguration, $this->senderConfiguration, $this->serializer);
    }

    /** @throws \JsonException */
    public function testGetMethod(): void
    {
        $body = json_encode(['id' => (string) Uuid::v4()], \JSON_THROW_ON_ERROR);
        $kMessage = new KafkaMessage($body);
        $kStamp = MessageHelper::createKafkaStamp($body);
        $kReceivedStamp = MessageHelper::createKafkaReceivedStamp($kStamp);

        $this->connection
            ->expects(self::once())
            ->method('get')
            ->willReturn($kStamp)
        ;

        $this->serializer
            ->expects(self::once())
            ->method('decode')
            ->willReturn(MessageHelper::createEnvelope($kMessage, [$kReceivedStamp]))
        ;

        $envelopes = ArrayList::collect($this->transport->get());

        self::assertSame($kReceivedStamp->message, $envelopes->firstElement()->get()->last(KafkaReceivedStamp::class)?->message);
        self::assertSame($kReceivedStamp->topicName, $envelopes->firstElement()->get()->last(KafkaReceivedStamp::class)?->topicName);
    }

    /** @throws \JsonException */
    public function testAckMethod(): void
    {
        $body = json_encode(['id' => (string) Uuid::v4()], \JSON_THROW_ON_ERROR);
        $kMessage = new KafkaMessage($body);
        $kStamp = MessageHelper::createKafkaStamp($body);
        $kReceivedStamp = MessageHelper::createKafkaReceivedStamp($kStamp);
        $envelope = MessageHelper::createEnvelope($kMessage, [$kReceivedStamp]);

        $this->connection
            ->expects(self::once())
            ->method('ack')
        ;

        $this->transport->ack($envelope);
    }

    /** @throws \JsonException */
    public function testRejectMethod(): void
    {
        $body = json_encode(['id' => (string) Uuid::v4()], \JSON_THROW_ON_ERROR);
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

        $this->transport->reject($envelope);
    }

    /** @throws \JsonException */
    public function testSendMethod(): void
    {
        $body = json_encode(['id' => (string) Uuid::v4()], \JSON_THROW_ON_ERROR);
        $kMessage = new KafkaMessage($body);
        $kStamp = MessageHelper::createKafkaStamp($body);
        $kReceivedStamp = MessageHelper::createKafkaReceivedStamp($kStamp);
        $envelope = MessageHelper::createEnvelope($kMessage, [$kReceivedStamp]);

        $this->connection
            ->expects(self::once())
            ->method('send')
        ;

        $this->transport->send($envelope);
    }
}
