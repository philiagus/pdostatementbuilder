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

class IgnoredIfElementsTest extends SuccessUnit
{


    protected function buildStatement(Builder $builder, array $further): Statement
    {
        $resource = fopen('php://input', 'r');

        return $builder->build(
            "{$builder->if(false)}
                    {$builder->value($resource)}
                    {$builder->in($resource)}
                {$builder->else()}{$builder->value(3)}{$builder->in([1])}{$builder->endif()}"
        );
    }

    protected function getStatement(): string
    {
        return "{INT:3}{INT:1}";
    }
}
