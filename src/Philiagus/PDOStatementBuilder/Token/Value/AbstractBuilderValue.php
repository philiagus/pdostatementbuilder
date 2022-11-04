<?php
/**
 * This file is part of philiagus/pdostatementbuilder
 *
 * (c) Andreas Bittner <philiagus@philiagus.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Philiagus\PDOStatementBuilder\Token\Value;

use ArrayAccess;
use Philiagus\PDOStatementBuilder\BuilderValue;

abstract class AbstractBuilderValue implements BuilderValue, ArrayAccess
{

    public function offsetExists(mixed $offset): bool
    {
        throw new \LogicException('offset exists cannot be called on BuilderValues');
    }

    public function offsetGet(mixed $offset): ArrayAccessValue
    {
        return new ArrayAccessValue($this, $offset);
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        throw new \LogicException('offset set cannot be called on BuilderValues');
    }

    public function offsetUnset(mixed $offset): void
    {
        throw new \LogicException('offset unset cannot be called on BuilderValues');
    }

    public function __call($name, $arguments): CallValue
    {
        return new CallValue($this, $name, $arguments);
    }

    public function __invoke(...$arguments): InvokeValue
    {
        return new InvokeValue($this, $arguments);
    }
}
