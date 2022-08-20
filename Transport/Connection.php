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

use FRZB\Component\Messenger\Bridge\Kafka\Enum\ResponseCode;
use FRZB\Component\Messenger\Bridge\Kafka\Exception\ConnectionException;
use FRZB\Component\Messenger\Bridge\Kafka\Exception\KafkaException;
use RdKafka\Exception;
use RdKafka\KafkaConsumer;
use RdKafka\Producer as KafkaProducer;

/**
 * @author Mykhailo Shtanko <fractalzombie@gmail.com>
 */
class Connection
{
    private KafkaConsumer $consumer;
    private KafkaProducer $producer;

    public function __construct(
        private readonly KafkaFactory $factory,
    ) {
    }

    /** @throws ConnectionException|KafkaException */
    public function get(KafkaReceiverConfiguration $configuration): KafkaStamp
    {
        try {
            $message = $this->getConsumer($configuration)->consume($configuration->receiveTimeout);
        } catch (\Throwable $e) {
            throw ConnectionException::fromThrowable($e);
        }

        return match (ResponseCode::tryFrom($message->err)) {
            ResponseCode::NoError => KafkaStamp::fromMessage($message),
            ResponseCode::PartitionEof => throw KafkaException::partitionEof($message),
            ResponseCode::TimedOut => throw KafkaException::timeOut($message),
            ResponseCode::Transport => throw KafkaException::transportError($message),
            ResponseCode::UnknownTopicOrPart => throw KafkaException::unknownTopicOrPartition($message),
            ResponseCode::TopicException => throw KafkaException::topicException($message),
            default => throw ConnectionException::fromMessage($message),
        };
    }

    /** @throws ConnectionException */
    public function ack(KafkaStamp $message, KafkaReceiverConfiguration $configuration): void
    {
        try {
            $configuration->isCommitAsync
                ? $this->getConsumer($configuration)->commitAsync($message->message)
                : $this->getConsumer($configuration)->commit($message->message);
        } catch (Exception $e) {
            throw ConnectionException::fromThrowable($e);
        }
    }

    /** @throws ConnectionException */
    public function send(array $payload, KafkaSenderConfiguration $configuration): void
    {
        $topic = $this->getProducer()->newTopic($configuration->topicName);

        $topic->producev(
            $configuration->messagePartition,
            $configuration->messageFlag->getFlag(),
            $payload['body'],
            $payload['key'] ?? $configuration->messageKey,
            $payload['headers'] ?? [],
            $payload['timestamp_ms'] ?? (new \DateTimeImmutable())->getTimestamp(),
        );

        for ($retry = 0; $retry < $configuration->flushRetries; ++$retry) {
            $code = $this->getProducer()->flush($configuration->flushTimeout);
            $isLastTry = $retry === $configuration->flushRetries - 1;
            $isFlushed = RD_KAFKA_RESP_ERR_NO_ERROR === $code;

            if (!$isFlushed && $isLastTry) {
                throw new ConnectionException(sprintf('Producer error: %s', $code), $code);
            }

            if ($isFlushed) {
                break;
            }
        }
    }

    private function getProducer(): KafkaProducer
    {
        return $this->producer ??= $this->factory->createProducer();
    }

    private function getConsumer(KafkaReceiverConfiguration $configuration): KafkaConsumer
    {
        try {
            $this->consumer ??= $this->factory->createConsumer();

            if (!$configuration->isSubscribed) {
                $this->consumer->subscribe([$configuration->topicName]);
                $configuration->isSubscribed = true;
            }

            return $this->consumer;
        } catch (\Throwable $e) {
            throw ConnectionException::fromThrowable($e);
        }
    }
}
