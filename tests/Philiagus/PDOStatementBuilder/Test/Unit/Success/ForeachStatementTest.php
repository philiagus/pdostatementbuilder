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

class ForeachStatementTest extends SuccessUnit
{

    protected function buildStatement(Builder $builder, array $further): Statement
    {
        return $builder->build(
            "a{$builder->foreach(['a' => 1, 'b' => 2], $value, $key)}" .
            " {$builder->value($key)} {$builder->value($value)}" .
            "{$builder->endforeach()} b"
        );
    }

    protected function getStatement(): string
    {
        return 'a {STR:"a"} {INT:1} {STR:"b"} {INT:2} b';
    }
}
