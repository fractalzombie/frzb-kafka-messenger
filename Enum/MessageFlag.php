<?php

namespace FRZB\Component\Messenger\Bridge\Kafka\Enum;

/**
 * @internal
 *
 * @author Mykhailo Shtanko <fractalzombie@gmail.com>
 */
enum MessageFlag: string
{
    case Default = 'default';
    case Free = 'free'; // Delegate freeing of payload
    case Copy = 'copy'; // Make a copy of the payload.
    case Block = 'block'; // Block produce on message queue full
    case Partition = 'partition';

    public function getFlag(): int
    {
        return match ($this) {
            self::Default => 0,
            self::Free => 1,
            self::Copy => 2,
            self::Block => 4,
            self::Partition => 8,
        };
    }
}
