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
use Philiagus\PDOStatementBuilder\Token\Value\ForeachInfoValue;

class ForeachInfoTest extends SuccessUnit
{

    protected function buildStatement(Builder $builder, array $further): Statement
    {
        /** @var ForeachInfoValue $i */
        return $builder->build(
            'start' .
            $builder->foreach(['a', 'b', 'c'], $v, info: $i) .
            ' ' . $builder->raw($i->index) . ' of ' . $builder->raw($i->count) . ' ' .
            $builder->if($i->first) .
            'first ' .
            $builder->endif() .
            $builder->raw($v) .
            $builder->if($i->last) .
            ' last ' .
            $builder->endif() .
            $builder->endforeach() .
            'end'
        );
    }

    protected function getStatement(): string
    {
        return 'start 0 of 3 first a 1 of 3 b 2 of 3 c last end';
    }
}
