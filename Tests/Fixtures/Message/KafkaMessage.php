<?php

declare(strict_types=1);

namespace FRZB\Component\Messenger\Bridge\Kafka\Tests\Fixtures\Message;

use JetBrains\PhpStorm\Immutable;

#[Immutable]
final class KafkaMessage
{
    public function __construct(
        public readonly string $message,
    ) {
    }
}
