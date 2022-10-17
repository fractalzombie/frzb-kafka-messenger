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

use JetBrains\PhpStorm\Pure;

/**
 * @author Mykhailo Shtanko <fractalzombie@gmail.com>
 */
final class ExtensionException extends \LogicException
{
    private const NO_EXTENSION_MESSAGE = 'You cannot use the "%s" as the "%s" extension is not installed.';
    private const LIBRARY_VERSION_MISMATCH_MESSAGE = 'You must install %s %s %s';
    private const CONSTANT_NOT_DEFINED_MESSAGE = '%s constant is not defined. %s is probably not installed';

    #[Pure]
    public static function noExtension(string $targetClass, string $extension): self
    {
        return new self(sprintf(self::NO_EXTENSION_MESSAGE, $targetClass, $extension));
    }

    #[Pure]
    public static function libraryVersionMismatch(string $library, string $operand, string $version): self
    {
        return new self(sprintf(self::LIBRARY_VERSION_MISMATCH_MESSAGE, $library, $operand, $version));
    }

    #[Pure]
    public static function constantNotDefined(string $constant, string $library): self
    {
        return new self(sprintf(self::CONSTANT_NOT_DEFINED_MESSAGE, $constant, $library));
    }
}
