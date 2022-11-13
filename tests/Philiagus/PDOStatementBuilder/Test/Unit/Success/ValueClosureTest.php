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

class ValueClosureTest extends SuccessUnit
{
    protected function buildStatement(Builder $builder, array $further): Statement
    {
        return $builder->build(
            $builder->value('a', closure: fn(string $v) => strtoupper($v)) .
            $builder->foreach(['b','c'], $value, $key) .
            $builder->value($key, closure: function($key, &$type) {
                $type = \PDO::PARAM_BOOL;
                return $key + 100;
            }) .
            $builder->value($value, closure: fn($v) => strtoupper($v)) .
            $builder->endforeach()
        );
    }

    protected function getStatement(): string
    {
        return '{STR:"A"}{BOOL:100}{STR:"B"}{BOOL:101}{STR:"C"}';
    }
}
