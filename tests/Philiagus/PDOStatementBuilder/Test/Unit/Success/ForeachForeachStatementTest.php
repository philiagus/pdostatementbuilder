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

namespace Philiagus\PDOStatementBuilder\Test\Unit\Success;

use Philiagus\PDOStatementBuilder\Builder;
use Philiagus\PDOStatementBuilder\Statement;
use Philiagus\PDOStatementBuilder\Test\Unit\SuccessUnit;

class ForeachForeachStatementTest extends SuccessUnit
{

    protected function buildStatement(Builder $builder, array $further): Statement
    {
        return $builder->build(
            "a{$builder->foreach(['a' => 1, 'b' => 2], $value1, $key1)}" .
            " {$builder->value($key1)}§{$builder->value($value1)}" .
            "{$builder->foreach(['f2', 'f2.2'], $value2, $key2)}" .
            " {$builder->value($key2)}!{$builder->value($value2)}" .
            "{$builder->endforeach()}" .
            " {$builder->value($key1)}?{$builder->value($value1)}" .
            "{$builder->endforeach()} b"
        );
    }

    protected function getStatement(): string
    {
        return 'a' .
            ' {STR:"a"}§{INT:1}' .
            ' {INT:0}!{STR:"f2"}' .
            ' {INT:1}!{STR:"f2.2"}' .
            ' {STR:"a"}?{INT:1}' .
            ' {STR:"b"}§{INT:2}' .
            ' {INT:0}!{STR:"f2"}' .
            ' {INT:1}!{STR:"f2.2"}' .
            ' {STR:"b"}?{INT:2}' .
            ' b';
    }
}