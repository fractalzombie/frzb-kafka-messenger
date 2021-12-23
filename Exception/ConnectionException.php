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
final class ConnectionException extends \LogicException
{
    public static function fromThrowable(\Throwable $previous): self
    {
        return new self($previous->getMessage(), $previous->getCode(), $previous);
    }

    public static function fromMessage(Message $message): self
    {
        return new self($message->errstr(), $message->err);
    }
}
