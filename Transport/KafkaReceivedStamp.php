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

use Symfony\Component\Messenger\Stamp\NonSendableStampInterface;

/**
 * Stamp applied when a message is received from Kafka.
 *
 * @author Mykhailo Shtanko <fractalzombie@gmail.com>
 */
class KafkaReceivedStamp implements NonSendableStampInterface
{
    private KafkaStamp $message;
    private string $topicName;

    public function __construct(KafkaStamp $message, string $topicName)
    {
        $this->message = $message;
        $this->topicName = $topicName;
    }

    public function getMessage(): KafkaStamp
    {
        return $this->message;
    }

    public function getTopicName(): string
    {
        return $this->topicName;
    }
}
