<?php
/*
 * This file is part of philiagus/pdostatementbuilder
 *
 * (c) Andreas Bittner <philiagus@philiagus.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Philiagus\PDOStatementBuilder\Token\Value;

use Philiagus\PDOStatementBuilder\BuilderValue;

class GetValue extends AbstractBuilderValue
{

    public function __construct(
        private BuilderValue $sourceValue,
        private string $field
    )
    {

    }

    /**
     * @inheritDoc
     */
    public function resolveAsPDOStatementBuilderValue(): mixed
    {
        return $this->sourceValue->resolveAsPDOStatementBuilderValue()->{$this->field};
    }
}
