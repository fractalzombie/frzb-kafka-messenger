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

use FRZB\Component\Messenger\Bridge\Kafka\Enum\MessageFlag;
use JetBrains\PhpStorm\Immutable;

/**
 * @author Mykhailo Shtanko <fractalzombie@gmail.com>
 */
#[Immutable]
final class KafkaSenderConfiguration
{
    public function __construct(
        public readonly string $topicName,
        public readonly int $flushTimeout,
        public readonly int $flushRetries,
        public readonly MessageFlag $messageFlag,
        public readonly int $messagePartition,
        public readonly ?string $messageKey,
    ) {
    }
}
