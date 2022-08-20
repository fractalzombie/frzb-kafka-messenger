<?php

declare(strict_types=1);

namespace FRZB\Component\Messenger\Bridge\Kafka\Tests\Transport;

use Fp\Collections\ArrayList;
use FRZB\Component\Messenger\Bridge\Kafka\Exception\ConnectionException;
use FRZB\Component\Messenger\Bridge\Kafka\Exception\KafkaException;
use FRZB\Component\Messenger\Bridge\Kafka\Exception\TransportException;
use FRZB\Component\Messenger\Bridge\Kafka\Tests\Fixtures\KafkaMessage;
use FRZB\Component\Messenger\Bridge\Kafka\Tests\Helper\MessageHelper;
use FRZB\Component\Messenger\Bridge\Kafka\Transport\Connection;
use FRZB\Component\Messenger\Bridge\Kafka\Transport\KafkaReceivedStamp;
use FRZB\Component\Messenger\Bridge\Kafka\Transport\KafkaReceiver;
use FRZB\Component\Messenger\Bridge\Kafka\Transport\KafkaReceiverConfiguration;
use PHPUnit\Framework\MockObject\Rule\InvocationOrder;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Stamp\TransportMessageIdStamp;
use Symfony\Component\Messenger\Transport\Sender\SenderInterface;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface as Serializer;
use Symfony\Component\Uid\Uuid;

/**
 * @requires extension rdkafka
 *
 * @group kafka-messenger
 *
 * @internal
 */
class KafkaReceiverTest extends TestCase
{
    private KafkaReceiverConfiguration $configuration;
    private Connection $connection;
    private Serializer $serializer;
    private KafkaReceiver $receiver;
    private SenderInterface $sender;

    protected function setUp(): void
    {
        $this->configuration = new KafkaReceiverConfiguration('test_topic', 10000, true);
        $this->connection = $this->createMock(Connection::class);
        $this->serializer = $this->createMock(Serializer::class);
        $this->sender = $this->createMock(SenderInterface::class);
        $this->receiver = new KafkaReceiver($this->connection, $this->sender, $this->configuration, $this->serializer);
    }

    /** @dataProvider getMethodSuccessProvider */
    public function testGetMethodSuccess(int $status, string $body, array $headers, object $message, array $stamps): void
    {
        $kStamp = MessageHelper::createKafkaStamp($body, $headers, status: $status);
        $envelope = MessageHelper::createEnvelope($message, $stamps);

        $this->connection
            ->expects(self::once())
            ->method('get')
            ->willReturn($kStamp)
        ;

        $this->serializer
            ->expects(self::once())
            ->method('decode')
            ->willReturn($envelope)
        ;

        $envelopes = ArrayList::collect($this->receiver->get());
        $receivedEnvelope = $envelopes->firstElement()->get();
        $receivedStamp = $receivedEnvelope?->last(KafkaReceivedStamp::class);
        $idStamp = $receivedEnvelope?->last(TransportMessageIdStamp::class);

        self::assertNotEmpty($envelopes);
        self::assertCount(1, $envelopes);
        self::assertSame($message, $envelopes->firstElement()->get()?->getMessage());

        self::assertNotNull($receivedStamp);
        self::assertInstanceOf(KafkaReceivedStamp::class, $receivedStamp);
        self::assertSame($kStamp->message, $receivedStamp->message->message);

        self::assertNotNull($idStamp);
        self::assertInstanceOf(TransportMessageIdStamp::class, $idStamp);
        self::assertSame(ArrayList::collect($stamps)->filterOf(TransportMessageIdStamp::class)->firstElement()->get(), $idStamp);
    }

    /** @throws \JsonException */
    public function getMethodSuccessProvider(): iterable
    {
        $id = (string) Uuid::v4();

        yield sprintf('%s with headers', KafkaMessage::class) => [
            'status' => RD_KAFKA_RESP_ERR_NO_ERROR,
            'body' => json_encode(['message' => 'KafkaMessage'], \JSON_THROW_ON_ERROR),
            'headers' => [
                'X-Message-Stamp-Symfony\Component\Messenger\Stamp\TransportMessageIdStamp' => json_encode(['id' => $id], \JSON_THROW_ON_ERROR),
                'Content-Type' => 'application/json',
                'type' => KafkaMessage::class,
            ],
            'message' => new KafkaMessage('KafkaMessage'),
            'stamps' => [new TransportMessageIdStamp($id)],
        ];
    }

    /** @dataProvider getMethodFailureProvider */
    public function testGetMethodFailure(\Throwable $e, bool $isKafkaException = false): void
    {
        $this->connection
            ->expects(self::once())
            ->method('get')
            ->willThrowException($e)
        ;

        $this->serializer
            ->expects(self::never())
            ->method('decode')
        ;

        if (!$isKafkaException) {
            $this->expectException(TransportException::class);
        }

        $envelopes = ArrayList::collect($this->receiver->get());

        self::assertCount(0, $envelopes);
    }

    public function getMethodFailureProvider(): iterable
    {
        yield sprintf('When %s thrown', ConnectionException::class) => [
            'exception' => new ConnectionException(),
        ];

        yield sprintf('When %s thrown', \JsonException::class) => [
            'exception' => new \JsonException(),
        ];

        yield sprintf('When %s thrown', KafkaException::class) => [
            'exception' => new KafkaException(),
            'is_kafka_exception' => true,
        ];
    }

    /** @dataProvider ackMethodProvider */
    public function testAckMethod(
        Envelope $envelope,
        InvocationOrder $ackExpects,
        InvocationOrder $logExpects,
        ?bool $isThrows = false,
        ?bool $isAckThrows = false,
        ?\Throwable $ackException = null,
    ): void {
        $ackMethod = $this->connection
            ->expects($ackExpects)
            ->method('ack')
        ;

        if ($isAckThrows) {
            $ackMethod->willThrowException($ackException);
        }

        if ($isThrows) {
            $this->expectException(TransportException::class);
        }

        $this->receiver->ack($envelope);
    }

    /** @throws \JsonException */
    public function ackMethodProvider(): iterable
    {
        $body = json_encode(['id' => (string) Uuid::v4()], \JSON_THROW_ON_ERROR);
        $kMessage = new KafkaMessage($body);
        $envelope = MessageHelper::createEnvelope($kMessage);

        yield 'Envelope without received stamp and failure' => [
            'envelope' => $envelope,
            'ack_expects' => self::never(),
            'log_expects' => self::never(),
            'is_throws' => true,
            'is_ack_throws' => false,
            'ack_exception' => null,
        ];

        $kStamp = MessageHelper::createKafkaStamp($body);
        $kReceivedStamp = MessageHelper::createKafkaReceivedStamp($kStamp);
        $envelope = MessageHelper::createEnvelope($kMessage, [$kReceivedStamp]);

        yield 'Envelope with received stamp and success' => [
            'envelope' => $envelope,
            'ack_expects' => self::once(),
            'log_expects' => self::once(),
            'is_throws' => false,
            'is_ack_throws' => false,
            'ack_exception' => null,
        ];

        yield sprintf('Envelope with received stamp and failure on connection ack with %s', ConnectionException::class) => [
            'envelope' => $envelope,
            'ack_expects' => self::once(),
            'log_expects' => self::once(),
            'is_throws' => true,
            'is_ack_throws' => true,
            'ack_exception' => new ConnectionException(),
        ];

        yield sprintf('Envelope with received stamp and failure on connection ack with %s', \JsonException::class) => [
            'envelope' => $envelope,
            'ack_expects' => self::once(),
            'log_expects' => self::once(),
            'is_throws' => true,
            'is_ack_throws' => true,
            'ack_exception' => new \JsonException(),
        ];
    }

    /** @dataProvider rejectMethodProvider */
    public function testRejectMethod(Envelope $envelope, bool $isRejectable = false): void
    {
        $this->configuration = new KafkaReceiverConfiguration('test_topic', 10000, true, $isRejectable);
        $this->receiver = new KafkaReceiver($this->connection, $this->sender, $this->configuration, $this->serializer);

        $this->sender
            ->expects($isRejectable ? self::once() : self::never())
            ->method('send')
            ->willReturn($envelope)
        ;

        $this->receiver->reject($envelope);
    }

    /** @throws \JsonException */
    public function rejectMethodProvider(): iterable
    {
        $body = json_encode(['id' => (string) Uuid::v4()], \JSON_THROW_ON_ERROR);
        $kMessage = new KafkaMessage($body);
        $kStamp = MessageHelper::createKafkaStamp($body);
        $kReceivedStamp = MessageHelper::createKafkaReceivedStamp($kStamp);
        $envelope = MessageHelper::createEnvelope($kMessage, [$kReceivedStamp]);

        yield 'Rejectable' => [
            'envelope' => $envelope,
            'is_rejectable' => false,
        ];

        yield 'Non rejectable' => [
            'envelope' => $envelope,
            'is_rejectable' => true,
        ];
    }
}
