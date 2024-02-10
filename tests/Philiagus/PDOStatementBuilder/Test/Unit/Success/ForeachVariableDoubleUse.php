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


namespace Philiagus\PDOStatementBuilder\Test\Unit\Success;

use Philiagus\PDOStatementBuilder\Builder;
use Philiagus\PDOStatementBuilder\Statement;
use Philiagus\PDOStatementBuilder\Test\Unit\SuccessUnit;

class ForeachVariableDoubleUse extends SuccessUnit
{

    protected function buildStatement(Builder $builder, array $further): Statement
    {
        return $builder->build(
            '1' .
            $builder->foreach(['a', 'b'], $var) .
            '2' .
            $builder->raw($var) .
            '3' .
            $builder->foreach(['c', 'd'], $var) .
            '4' .
            $builder->raw($var) .
            '5' .
            $builder->endforeach() .
            '6' .
            $builder->raw($var) .
            '7' .
            $builder->endforeach() .
            '8'
        );
    }

    protected function getStatement(): string
    {
        return '12a34c54d56d72b34c54d56d78';
    }
}
