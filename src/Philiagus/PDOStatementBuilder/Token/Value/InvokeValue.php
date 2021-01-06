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

class InvokeValue extends AbstractBuilderValue
{

    /**
     * @var BuilderValue
     */
    private $value;
    /**
     * @var array
     */
    private $arguments;

    public function __construct(BuilderValue $value, array $arguments)
    {
        $this->value = $value;
        $this->arguments = $arguments;
    }

    public function resolveAsPDOStatementBuilderValue()
    {
        $arguments = [];
        foreach ($this->arguments as $key => $argument) {
            if ($argument instanceof BuilderValue) {
                $arguments[$key] = $argument->resolveAsPDOStatementBuilderValue();
            } else {
                $arguments[$key] = $argument;
            }
        }

        return ($this->value->resolveAsPDOStatementBuilderValue())(...$arguments);
    }

}