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

class WritableValue extends AbstractBuilderValue
{

    private mixed $value = null;
    private bool $valueSet = false;

    public function __construct()
    {
    }

    public function resolveAsPDOStatementBuilderValue()
    {
        if (!$this->valueSet) {
            throw new \LogicException(
                "Trying to resolve WritableValue without having a set value first"
            );
        }

        return $this->value;
    }

    public function setPDOStatementBuilderValue($value): self
    {
        $this->value = $value;
        $this->valueSet = true;

        return $this;
    }

}
