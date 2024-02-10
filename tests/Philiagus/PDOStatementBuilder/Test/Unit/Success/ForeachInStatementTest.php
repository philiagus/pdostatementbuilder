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

class ForeachInStatementTest extends SuccessUnit
{

    protected function buildStatement(Builder $builder, array $further): Statement
    {
        return $builder->build(
            "a{$builder->foreach([[1,2],[3,4],[5,6]], $value, $key)}" .
            " ({$builder->in($value)})" .
            "{$builder->endforeach()} b"
        );
    }

    protected function getStatement(): string
    {
        return 'a ({INT:1}, {INT:2}) ({INT:3}, {INT:4}) ({INT:5}, {INT:6}) b';
    }
}
