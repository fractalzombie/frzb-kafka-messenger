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
final class KafkaSenderConfiguration
{
    public function __construct(
        private string $topicName,
        private int $flushTimeout,
        private int $flushRetries,
    ) {
    }

    public function getTopicName(): string
    {
        return $this->topicName;
    }

    public function getFlushTimeout(): int
    {
        return $this->flushTimeout;
    }

    public function getFlushRetries(): int
    {
        return $this->flushRetries;
    }
}
