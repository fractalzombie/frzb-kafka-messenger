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
        private KafkaFactory $factory,
    ) {
    }

    /** @throws ConnectionException|KafkaException */
    public function get(KafkaReceiverConfiguration $configuration): KafkaStamp
    {
        try {
            $message = $this->getConsumer($configuration)->consume($configuration->getReceiveTimeout());
        } catch (Exception $e) {
            throw ConnectionException::fromThrowable($e);
        }

        return match ($message->err) {
            RD_KAFKA_RESP_ERR_NO_ERROR => KafkaStamp::fromMessage($message),
            RD_KAFKA_RESP_ERR__PARTITION_EOF => throw KafkaException::partitionEof($message),
            RD_KAFKA_RESP_ERR__TIMED_OUT => throw KafkaException::timeOut($message),
            RD_KAFKA_RESP_ERR__TRANSPORT => throw KafkaException::transportError($message),
            RD_KAFKA_RESP_ERR_UNKNOWN_TOPIC_OR_PART => throw KafkaException::unknownTopicOrPartition($message),
            RD_KAFKA_RESP_ERR_TOPIC_EXCEPTION => throw KafkaException::topicException($message),
            default => throw ConnectionException::fromMessage($message),
        };
    }

    /** @throws ConnectionException */
    public function ack(KafkaStamp $message, KafkaReceiverConfiguration $configuration): void
    {
        try {
            $configuration->isCommitAsync()
                ? $this->getConsumer($configuration)->commitAsync($message->getMessage())
                : $this->getConsumer($configuration)->commit($message->getMessage());
        } catch (Exception $e) {
            throw ConnectionException::fromThrowable($e);
        }
    }

    /** @throws ConnectionException */
    public function send(array $payload, KafkaSenderConfiguration $configuration): void
    {
        $topic = $this->getProducer()->newTopic($configuration->getTopicName());

        $topic->producev(
            RD_KAFKA_PARTITION_UA,
            0,
            $payload['body'],
            $payload['key'] ?? null,
            $payload['headers'] ?? [],
            $payload['timestamp_ms'] ?? null,
        );

        for ($retry = 0; $retry < $configuration->getFlushRetries(); ++$retry) {
            $code = $this->getProducer()->flush($configuration->getFlushTimeout());
            $isLastTry = $retry === $configuration->getFlushRetries() - 1;
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
        $this->consumer ??= $this->factory->createConsumer();

        if (!$configuration->isSubscribed()) {
            try {
                $this->consumer->subscribe([$configuration->getTopicName()]);
                $configuration->setSubscribed(true);
            } catch (Exception $e) {
                throw ConnectionException::fromThrowable($e);
            }
        }

        return $this->consumer;
    }
}
