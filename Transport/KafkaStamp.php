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
use RdKafka\Message;

/**
 * @author Mykhailo Shtanko <fractalzombie@gmail.com>
 */
final class KafkaStamp
{
    public function __construct(
        private Message $message,
        private string $topicName,
        private int $offset,
        private int $timestamp,
        private string $body = '',
        private array $headers = [],
        private int $partition = \RD_KAFKA_PARTITION_UA,
        private ?string $key = null,
        private bool $isRedelivered = false,
    ) {
    }

    #[Pure]
    public static function fromMessage(Message $message, bool $isRedelivered = false): self
    {
        return new self(
            $message,
            $message->topic_name,
            $message->offset,
            $message->timestamp,
            $message->payload,
            $message->headers,
            $message->partition,
            $message->key,
            $isRedelivered,
        );
    }

    public function getMessage(): Message
    {
        return $this->message;
    }

    public function getTopicName(): string
    {
        return $this->topicName;
    }

    public function getOffset(): int
    {
        return $this->offset;
    }

    public function getTimestamp(): int
    {
        return $this->timestamp;
    }

    public function getBody(): string
    {
        return $this->body;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function getPartition(): int
    {
        return $this->partition;
    }

    public function isRedelivered(): bool
    {
        return $this->isRedelivered;
    }

    public function getKey(): ?string
    {
        return $this->key;
    }
}
