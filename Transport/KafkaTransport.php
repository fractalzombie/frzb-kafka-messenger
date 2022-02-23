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

use JetBrains\PhpStorm\Pure;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;
use Symfony\Component\Messenger\Transport\TransportInterface;

/**
 * @author Mykhailo Shtanko <fractalzombie@gmail.com>
 */
class KafkaTransport implements TransportInterface
{
    #[Pure]
    public function __construct(
        private Connection $connection,
        private KafkaReceiverConfiguration $receiverConfiguration,
        private KafkaSenderConfiguration $senderConfiguration,
        private SerializerInterface $serializer,
    ) {
    }

    /** {@inheritdoc} */
    public function get(): iterable
    {
        return $this->getReceiver()->get();
    }

    /** {@inheritdoc} */
    public function ack(Envelope $envelope): void
    {
        $this->getReceiver()->ack($envelope);
    }

    /** {@inheritdoc} */
    public function reject(Envelope $envelope): void
    {
        $this->getReceiver()->reject($envelope);
    }

    /** {@inheritdoc} */
    public function send(Envelope $envelope): Envelope
    {
        return $this->getSender()->send($envelope);
    }

    private function getReceiver(): KafkaReceiver
    {
        return $this->receiver ??= new KafkaReceiver($this->connection, $this->getSender(), $this->receiverConfiguration, $this->serializer);
    }

    private function getSender(): KafkaSender
    {
        return $this->sender ??= new KafkaSender($this->connection, $this->senderConfiguration, $this->serializer);
    }
}
