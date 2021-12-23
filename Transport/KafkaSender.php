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
use FRZB\Component\Messenger\Bridge\Kafka\Exception\TransportException;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Transport\Sender\SenderInterface;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;

/**
 * Symfony Messenger sender to send messages to Kafka brokers using PHP's Kafka extension.
 *
 * @author Mykhailo Shtanko <fractalzombie@gmail.com>
 */
class KafkaSender implements SenderInterface
{
    public function __construct(
        private Connection $connection,
        private KafkaSenderConfiguration $configuration,
        private SerializerInterface $serializer,
        private KafkaLogger $logger,
    ) {
    }

    /** {@inheritdoc} */
    public function send(Envelope $envelope): Envelope
    {
        try {
            $payload = $this->serializer->encode($envelope);
            $this->connection->send($payload, $this->configuration);
            $this->logger->logProduce($payload);
        } catch (ConnectionException|\JsonException $e) {
            $this->logger->logError($e);

            throw TransportException::fromThrowable($e);
        }

        return $envelope;
    }
}
