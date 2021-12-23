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
use FRZB\Component\Messenger\Bridge\Kafka\Exception\TransportException;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Transport\Receiver\ReceiverInterface;
use Symfony\Component\Messenger\Transport\Sender\SenderInterface as KafkaSender;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface as Serializer;

/**
 * Symfony Messenger receiver to get messages from Kafka broker using PHP's Kafka extension.
 *
 * @author Mykhailo Shtanko <fractalzombie@gmail.com>
 */
class KafkaReceiver implements ReceiverInterface
{
    public function __construct(
        private Connection $connection,
        private KafkaSender $sender,
        private KafkaReceiverConfiguration $configuration,
        private Serializer $serializer,
        private KafkaLogger $logger
    ) {
    }

    /** {@inheritdoc} */
    public function get(): iterable
    {
        try {
            $message = $this->connection->get($this->configuration);

            $envelope = $this->serializer->decode([
                'topic_name' => $message->getTopicName(),
                'offset' => $message->getOffset(),
                'timestamp' => $message->getTimestamp(),
                'body' => $message->getBody(),
                'headers' => $message->getHeaders(),
                'partition' => $message->getPartition(),
                'key' => $message->getKey(),
                'is_redelivered' => $message->isRedelivered(),
            ]);

            $this->logger->logReceive($message);

            yield $envelope->with(new KafkaReceivedStamp($message, $message->getTopicName()));
        } catch (KafkaException $e) {
            $this->logger->logError($e);

            return [];
        } catch (ConnectionException|\JsonException $e) {
            throw new TransportException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /** {@inheritdoc} */
    public function ack(Envelope $envelope): void
    {
        $stamp = $envelope->last(KafkaReceivedStamp::class) ?? throw TransportException::noReceivedStamp($envelope);

        try {
            $this->connection->ack($stamp->getMessage(), $this->configuration);
            $this->logger->logAcknowledge($stamp->getMessage(), $this->configuration);
        } catch (ConnectionException|\JsonException $e) {
            $this->logger->logError($e);

            throw TransportException::fromThrowable($e);
        }
    }

    /** {@inheritdoc} */
    public function reject(Envelope $envelope): void
    {
        if ($this->configuration->isRejectable()) {
            $this->ack($envelope);
            $this->sender->send($envelope);
        }
    }
}
