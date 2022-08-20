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

use JetBrains\PhpStorm\Immutable;
use JetBrains\PhpStorm\Pure;
use RdKafka\Message;

/**
 * @author Mykhailo Shtanko <fractalzombie@gmail.com>
 */
#[Immutable]
final class KafkaStamp
{
    public function __construct(
        public readonly Message $message,
        public readonly string $topicName,
        public readonly int $offset,
        public readonly int $timestamp,
        public readonly string $body,
        public readonly array $headers,
        public readonly ?int $partition = null,
        public readonly ?string $key = null,
        public readonly bool $isRedelivered = false,
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
            $message->payload ?? '',
            $message->headers ?? [],
            $message->partition,
            $message->key,
            $isRedelivered,
        );
    }
}
