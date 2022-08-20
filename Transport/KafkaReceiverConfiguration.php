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

/**
 * @author Mykhailo Shtanko <fractalzombie@gmail.com>
 */
final class KafkaReceiverConfiguration
{
    public bool $isSubscribed = false;

    public function __construct(
        public readonly string $topicName,
        public readonly int $receiveTimeout,
        public readonly bool $isCommitAsync,
        public readonly bool $isRejectable = false,
    ) {
    }
}
