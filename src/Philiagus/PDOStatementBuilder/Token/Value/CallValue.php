<?php
/*
 * This file is part of philiagus/pdostatementbuilder
 *
 * (c) Andreas Eicher <philiagus@philiagus.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Philiagus\PDOStatementBuilder\Token\Value;

use Philiagus\PDOStatementBuilder\BuilderValue;

class CallValue extends AbstractBuilderValue
{

    /**
     * CallValue constructor.
     *
     * @param BuilderValue $sourceValue
     * @param string $methodName
     * @param array $arguments
     */
    public function __construct(
        private BuilderValue $sourceValue,
        private string $methodName,
        private array $arguments = []
    )
    {
    }

    /**
     * @inheritDoc
     */
    public function resolveAsPDOStatementBuilderValue(): mixed
    {
        $arguments = [];
        $methodName = $this->methodName;
        foreach ($this->arguments as $key => $argument) {
            if ($argument instanceof BuilderValue) {
                $arguments[$key] = $argument->resolveAsPDOStatementBuilderValue();
            } else {
                $arguments[$key] = $argument;
            }
        }

        return $this->sourceValue->resolveAsPDOStatementBuilderValue()->$methodName(...$arguments);
    }
}
