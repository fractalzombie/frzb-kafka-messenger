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
    private bool $isSubscribed = false;

    public function __construct(
        private string $topicName,
        private int $receiveTimeout,
        private bool $isCommitAsync,
        private bool $isRejectable = false,
    ) {
    }

    public function getTopicName(): string
    {
        return $this->topicName;
    }

    public function getReceiveTimeout(): int
    {
        return $this->receiveTimeout;
    }

    public function isCommitAsync(): bool
    {
        return $this->isCommitAsync;
    }

    public function isRejectable(): bool
    {
        return $this->isRejectable;
    }

    public function isSubscribed(): bool
    {
        return $this->isSubscribed;
    }

    public function setSubscribed(bool $isSubscribed): self
    {
        $this->isSubscribed = $isSubscribed;

        return $this;
    }
}
