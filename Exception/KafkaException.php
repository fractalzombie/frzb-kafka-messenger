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

namespace FRZB\Component\Messenger\Bridge\Kafka\Exception;

use RdKafka\Message;

/**
 * @author Mykhailo Shtanko <fractalzombie@gmail.com>
 */
final class KafkaException extends \LogicException
{
    private const PARTITION_EOF_ERROR_MESSAGE = 'Partition EOF reached. Waiting for next message. %s';
    private const TIME_OUT_ERROR_MESSAGE = 'Consumer timeout. %s';
    private const TRANSPORT_ERROR_MESSAGE = 'Broker transport failure. %s';
    private const UNKNOWN_TOPIC_OR_PARTITION_ERROR_MESSAGE = 'Unknown topic or partition. %s';
    private const TOPIC_EXCEPTION_ERROR_MESSAGE = 'Topic exception. %s';

    public static function partitionEof(Message $message): self
    {
        return new self(sprintf(self::PARTITION_EOF_ERROR_MESSAGE, $message->errstr()), $message->err);
    }

    public static function timeOut(Message $message): self
    {
        return new self(sprintf(self::TIME_OUT_ERROR_MESSAGE, $message->errstr()), $message->err);
    }

    public static function transportError(Message $message): self
    {
        return new self(sprintf(self::TRANSPORT_ERROR_MESSAGE, $message->errstr()), $message->err);
    }

    public static function unknownTopicOrPartition(Message $message): self
    {
        return new self(sprintf(self::UNKNOWN_TOPIC_OR_PARTITION_ERROR_MESSAGE, $message->errstr()), $message->err);
    }

    public static function topicException(Message $message): self
    {
        return new self(sprintf(self::TOPIC_EXCEPTION_ERROR_MESSAGE, $message->errstr()), $message->err);
    }
}
