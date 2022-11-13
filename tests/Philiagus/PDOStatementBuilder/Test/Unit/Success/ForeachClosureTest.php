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

class ForeachClosureTest extends SuccessUnit
{

    protected function buildStatement(Builder $builder, array $further): Statement
    {
        return $builder->build(
            $builder->foreach(['a', ['b', 'c']], $v) .
            $builder->foreach($v, $v2, closure: fn(mixed $v) => is_array($v) ? $v : ['X', 'x']) .
            $builder->raw($v2) .
            $builder->endforeach() .
            $builder->endforeach()
        );
    }

    protected function getStatement(): string
    {
        return 'Xxbc';
    }
}
