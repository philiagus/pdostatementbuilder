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

class ForeachIfConversionTest extends SuccessUnit
{

    protected function buildStatement(Builder $builder, array $further): Statement
    {
        return $builder->build(
            $builder->foreach(['a', 'b', 'c'], $v, $k) .
            'a' .
            $builder->if($v, function($v) {return $v === 'a';}) .
            $builder->value($v) .
            $builder->elseif($v, function($v) {return $v === 'b';}) .
            $builder->value($k) .
            $builder->else() .
            $builder->value(true) .
            $builder->endif() .
            'b' .
            $builder->endforeach()
        );
    }

    protected function getStatement(): string
    {
        return 'a{STR:"a"}ba{INT:1}ba{BOOL:true}b';
    }
}