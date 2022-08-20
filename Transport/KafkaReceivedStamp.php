<?php

declare(strict_types=1);

/*
 * This file is part of the Symfony package.
 *
 * (c) Mykhailo Shtanko <fractalzombie@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FRZB\Component\Messenger\Bridge\Kafka\Transport;

use JetBrains\PhpStorm\Immutable;
use Symfony\Component\Messenger\Stamp\NonSendableStampInterface;

/**
 * Stamp applied when a message is received from Kafka.
 *
 * @author Mykhailo Shtanko <fractalzombie@gmail.com>
 */
#[Immutable]
class KafkaReceivedStamp implements NonSendableStampInterface
{
    public function __construct(
        public readonly KafkaStamp $message,
        public readonly string $topicName
    ) {
    }
}
