<?php

declare(strict_types=1);

namespace FRZB\Component\Messenger\Bridge\Kafka\Tests\Transport;

use FRZB\Component\Messenger\Bridge\Kafka\Exception\ConnectionException;
use FRZB\Component\Messenger\Bridge\Kafka\Exception\KafkaException;
use FRZB\Component\Messenger\Bridge\Kafka\Helper\ConfigHelper;
use FRZB\Component\Messenger\Bridge\Kafka\Tests\Helper\MessageHelper;
use FRZB\Component\Messenger\Bridge\Kafka\Tests\Helper\OptionsHelper;
use FRZB\Component\Messenger\Bridge\Kafka\Transport\Connection;
use FRZB\Component\Messenger\Bridge\Kafka\Transport\KafkaFactory;
use PHPUnit\Framework\MockObject\Rule\InvocationOrder;
use PHPUnit\Framework\TestCase;
use RdKafka\Exception;
use RdKafka\KafkaConsumer;
use RdKafka\Producer as KafkaProducer;
use RdKafka\ProducerTopic;
use Symfony\Component\Uid\Uuid;

/**
 * @requires extension rdkafka
 *
 * @group kafka-messenger
 *
 * @internal
 */
class ConnectionTest extends TestCase
{
    private KafkaFactory $factory;
    private Connection $connection;

    private KafkaConsumer $kafkaConsumer;
    private KafkaProducer $kafkaProducer;

    private array $options;

    protected function setUp(): void
    {
        $this->options = OptionsHelper::getOptions();
        $this->kafkaConsumer = $this->createMock(KafkaConsumer::class);
        $this->kafkaProducer = $this->createMock(KafkaProducer::class);
        $this->factory = $this->createMock(KafkaFactory::class);
        $this->connection = new Connection($this->factory);
    }

    /** @dataProvider getMethodProvider */
    public function testGetMethod(
        array $args,
        InvocationOrder $consumeExpects,
        InvocationOrder $subscribeExpects,
        ?bool $isExpectedThrows = false,
        ?string $expectedExceptionClass = null,
        ?bool $isConsumeThrows = false,
        ?\Throwable $consumeException = null,
        ?bool $isSubscribeThrows = false,
        ?\Throwable $subscribeException = null,
    ): void {
        $configuration = ConfigHelper::createReceiverConfig($this->options);
        $sMessage = MessageHelper::createKafkaMessage(...$args);

        $subscribeMethod = $this->kafkaConsumer
            ->expects($subscribeExpects)
            ->method('subscribe')
        ;

        $consumeMethod = $this->kafkaConsumer
            ->expects($consumeExpects)
            ->method('consume')
            ->willReturn($sMessage)
        ;

        $this->factory
            ->expects(self::once())
            ->method('createConsumer')
            ->willReturn($this->kafkaConsumer)
        ;

        if ($isSubscribeThrows && $subscribeException) {
            $subscribeMethod->willThrowException($subscribeException);
        }

        if ($isConsumeThrows && $consumeException) {
            $consumeMethod->willThrowException($consumeException);
        }

        if ($isExpectedThrows && $expectedExceptionClass) {
            $this->expectException($expectedExceptionClass);
        }

        $rMessage = $this->connection->get($configuration);

        self::assertNotNull($rMessage);
        self::assertSame($sMessage, $rMessage->message);
        self::assertSame($args['error'], $sMessage->err);
        self::assertSame($args['error'], $rMessage->message->err);
    }

    /** @throws \JsonException */
    public function getMethodProvider(): iterable
    {
        $payload = json_encode(['id' => (string) Uuid::v4()], \JSON_THROW_ON_ERROR);

        yield 'RD_KAFKA_RESP_ERR_NO_ERROR' => [
            'args' => [
                'payload' => $payload,
                'error' => RD_KAFKA_RESP_ERR_NO_ERROR,
            ],
            'consume_expects' => self::once(),
            'subscribe_expects' => self::once(),
        ];

        yield 'RD_KAFKA_RESP_ERR_NO_ERROR, but subscribe throws' => [
            'args' => [
                'payload' => $payload,
                'error' => RD_KAFKA_RESP_ERR_NO_ERROR,
            ],
            'consume_expects' => self::never(),
            'subscribe_expects' => self::once(),
            'is_expected_throws' => true,
            'expected_exception_class' => ConnectionException::class,
            'is_consume_throws' => false,
            'consume_exception' => null,
            'is_subscribe_throws' => true,
            'subscribe_exception' => new Exception(),
        ];

        yield 'RD_KAFKA_RESP_ERR_NO_ERROR, but consume throws' => [
            'args' => [
                'payload' => $payload,
                'error' => RD_KAFKA_RESP_ERR_NO_ERROR,
            ],
            'consume_expects' => self::once(),
            'subscribe_expects' => self::once(),
            'is_expected_throws' => true,
            'expected_exception' => ConnectionException::class,
            'is_consume_throws' => true,
            'consume_exception' => new Exception(),
            'is_subscribe_throws' => false,
            'subscribe_exception' => null,
        ];

        yield sprintf('RD_KAFKA_RESP_ERR__PARTITION_EOF %s', KafkaException::class) => [
            'args' => [
                'payload' => $payload,
                'error' => RD_KAFKA_RESP_ERR__PARTITION_EOF,
            ],
            'consume_expects' => self::once(),
            'subscribe_expects' => self::once(),
            'is_expected_throws' => true,
            'expected_exception' => KafkaException::class,
            'is_consume_throws' => false,
            'consume_exception' => new KafkaException(),
            'is_subscribe_throws' => false,
            'subscribe_exception' => null,
        ];

        yield sprintf('RD_KAFKA_RESP_ERR__TIMED_OUT %s', KafkaException::class) => [
            'args' => [
                'payload' => $payload,
                'error' => RD_KAFKA_RESP_ERR__TIMED_OUT,
            ],
            'consume_expects' => self::once(),
            'subscribe_expects' => self::once(),
            'is_expected_throws' => true,
            'expected_exception' => KafkaException::class,
            'is_consume_throws' => false,
            'consume_exception' => new KafkaException(),
            'is_subscribe_throws' => false,
            'subscribe_exception' => null,
        ];

        yield sprintf('RD_KAFKA_RESP_ERR__TRANSPORT %s', KafkaException::class) => [
            'args' => [
                'payload' => $payload,
                'error' => RD_KAFKA_RESP_ERR__TRANSPORT,
            ],
            'consume_expects' => self::once(),
            'subscribe_expects' => self::once(),
            'is_expected_throws' => true,
            'expected_exception' => KafkaException::class,
            'is_consume_throws' => false,
            'consume_exception' => new KafkaException(),
            'is_subscribe_throws' => false,
            'subscribe_exception' => null,
        ];

        yield sprintf('RD_KAFKA_RESP_ERR_UNKNOWN_TOPIC_OR_PART %s', KafkaException::class) => [
            'args' => [
                'payload' => $payload,
                'error' => RD_KAFKA_RESP_ERR_UNKNOWN_TOPIC_OR_PART,
            ],
            'consume_expects' => self::once(),
            'subscribe_expects' => self::once(),
            'is_expected_throws' => true,
            'expected_exception' => KafkaException::class,
            'is_consume_throws' => false,
            'consume_exception' => new KafkaException(),
            'is_subscribe_throws' => false,
            'subscribe_exception' => null,
        ];

        yield sprintf('RD_KAFKA_RESP_ERR_TOPIC_EXCEPTION %s', KafkaException::class) => [
            'args' => [
                'payload' => $payload,
                'error' => RD_KAFKA_RESP_ERR_TOPIC_EXCEPTION,
            ],
            'consume_expects' => self::once(),
            'subscribe_expects' => self::once(),
            'is_expected_throws' => true,
            'expected_exception' => KafkaException::class,
            'is_consume_throws' => false,
            'consume_exception' => new KafkaException(),
            'is_subscribe_throws' => false,
            'subscribe_exception' => null,
        ];

        yield sprintf('ALL_OTHER_ERRORS throws %s', ConnectionException::class) => [
            'args' => [
                'payload' => $payload,
                'error' => RD_KAFKA_RESP_ERR_UNKNOWN,
            ],
            'consume_expects' => self::once(),
            'subscribe_expects' => self::once(),
            'is_expected_throws' => true,
            'expected_exception' => ConnectionException::class,
            'is_consume_throws' => false,
            'consume_exception' => new ConnectionException(),
            'is_subscribe_throws' => false,
            'subscribe_exception' => null,
        ];
    }

    /** @dataProvider ackMethodProvider */
    public function testAckMethod(array $args, bool $isAsync = false, bool $isThrows = false): void
    {
        $this->options = OptionsHelper::getOptions(isCommitAsync: $isAsync);
        $commitMethodName = $isAsync ? 'commitAsync' : 'commit';
        $configuration = ConfigHelper::createReceiverConfig($this->options);
        $sMessage = MessageHelper::createKafkaStamp(...$args);

        $commitMethod = $this->kafkaConsumer
            ->expects(self::once())
            ->method($commitMethodName)
        ;

        if ($isThrows) {
            $commitMethod->willThrowException(new Exception());
            $this->expectException(ConnectionException::class);
        }

        $this->factory
            ->expects(self::once())
            ->method('createConsumer')
            ->willReturn($this->kafkaConsumer)
        ;

        $this->connection->ack($sMessage, $configuration);
    }

    /** @throws \JsonException */
    public function ackMethodProvider(): iterable
    {
        $payload = json_encode(['id' => (string) Uuid::v4()], \JSON_THROW_ON_ERROR);

        yield 'Acknowledge sync with success' => [
            'args' => [
                'payload' => $payload,
            ],
            'is_async' => false,
            'is_throws' => false,
        ];

        yield 'Acknowledge async with success' => [
            'args' => [
                'payload' => $payload,
            ],
            'is_async' => true,
            'is_throws' => false,
        ];

        yield 'Acknowledge sync with failure' => [
            'args' => [
                'payload' => $payload,
            ],
            'is_async' => false,
            'is_throws' => true,
        ];

        yield 'Acknowledge async with failure' => [
            'args' => [
                'payload' => $payload,
            ],
            'is_async' => true,
            'is_throws' => true,
        ];
    }

    /** @dataProvider sendMethodProvider */
    public function testSendMethod(array $payload, array $errors, int $retries, int $executeRetries, bool $isThrows = false): void
    {
        $this->options = OptionsHelper::getOptions(flushRetries: $retries);
        $configuration = ConfigHelper::createSenderConfig($this->options);

        $topic = $this->createMock(ProducerTopic::class);

        $topic
            ->expects(self::once())
            ->method('producev')
        ;

        $this->kafkaProducer
            ->expects(self::once())
            ->method('newTopic')
            ->willReturn($topic)
        ;

        $this->kafkaProducer
            ->expects(self::exactly($executeRetries))
            ->method('flush')
            ->willReturnOnConsecutiveCalls(...$errors)
        ;

        $this->factory
            ->expects(self::once())
            ->method('createProducer')
            ->willReturn($this->kafkaProducer)
        ;

        if ($isThrows) {
            $this->expectException(ConnectionException::class);
        }

        $this->connection->send($payload, $configuration);
    }

    /** @throws \JsonException */
    public function sendMethodProvider(): iterable
    {
        $payload = [
            'body' => json_encode(['id' => (string) Uuid::v4()], \JSON_THROW_ON_ERROR),
            'key' => null,
            'headers' => [],
            'timestamp_ms' => (new \DateTimeImmutable())->getTimestamp(),
        ];

        yield 'send without flush' => [
            'payload' => $payload,
            'errors' => [],
            'retries' => 0,
            'execute_retries' => 0,
            'is_throws' => false,
        ];

        yield sprintf('send with flush where retries %s and success is %s', 4, 4) => [
            'payload' => $payload,
            'errors' => [
                RD_KAFKA_RESP_ERR__TIMED_OUT,
                RD_KAFKA_RESP_ERR__TIMED_OUT,
                RD_KAFKA_RESP_ERR__TIMED_OUT,
                RD_KAFKA_RESP_ERR_NO_ERROR,
            ],
            'retries' => 4,
            'execute_retries' => 4,
            'is_throws' => false,
        ];

        yield sprintf('send with flush where retries %s and success is %s', 4, 2) => [
            'payload' => $payload,
            'errors' => [
                RD_KAFKA_RESP_ERR__TIMED_OUT,
                RD_KAFKA_RESP_ERR_NO_ERROR,
                RD_KAFKA_RESP_ERR__TIMED_OUT,
                RD_KAFKA_RESP_ERR__TIMED_OUT,
            ],
            'retries' => 4,
            'execute_retries' => 2,
            'is_throws' => false,
        ];

        yield sprintf('send with flush when all retries is failure and throws %s', ConnectionException::class) => [
            'payload' => $payload,
            'errors' => [
                RD_KAFKA_RESP_ERR__TIMED_OUT,
                RD_KAFKA_RESP_ERR__TIMED_OUT,
                RD_KAFKA_RESP_ERR__TIMED_OUT,
                RD_KAFKA_RESP_ERR__TIMED_OUT,
                RD_KAFKA_RESP_ERR__TIMED_OUT,
                RD_KAFKA_RESP_ERR__TIMED_OUT,
                RD_KAFKA_RESP_ERR__TIMED_OUT,
                RD_KAFKA_RESP_ERR__TIMED_OUT,
            ],
            'retries' => 8,
            'execute_retries' => 8,
            'is_throws' => true,
        ];
    }
}
