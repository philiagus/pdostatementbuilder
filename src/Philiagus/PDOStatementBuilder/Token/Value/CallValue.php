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

class CallValue extends AbstractBuilderValue
{

    /**
     * @var BuilderValue
     */
    private $sourceValue;
    /**
     * @var string
     */
    private $methodName;
    /**
     * @var array
     */
    private $arguments;

    /**
     * CallValue constructor.
     *
     * @param BuilderValue $sourceValue
     * @param string $methodName
     * @param array $arguments
     */
    public function __construct(BuilderValue $sourceValue, string $methodName, array $arguments = [])
    {
        $this->sourceValue = $sourceValue;
        $this->methodName = $methodName;
        $this->arguments = $arguments;
    }

    /**
     * @inheritDoc
     */
    public function resolveAsPDOStatementBuilderValue()
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