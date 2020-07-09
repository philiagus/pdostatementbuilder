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

class ForeachWithIfTest extends SuccessUnit
{

    protected function buildStatement(Builder $builder, array $further): Statement
    {
        return $builder->build(
            $builder->foreach(['a', 'b'], $v, $k) .
            'a' .
            $builder->if(false) .
            $builder->value($v) .
            $builder->endif() .
            $builder->if(true) .
            $builder->value($k) .
            $builder->endif() .
            'b' .
            $builder->endforeach()
        );
    }

    protected function getStatement(): string
    {
        return 'a{INT:0}ba{INT:1}b';
    }
}