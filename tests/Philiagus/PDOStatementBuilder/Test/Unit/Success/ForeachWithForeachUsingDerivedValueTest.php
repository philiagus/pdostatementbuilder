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

class ForeachWithForeachUsingDerivedValueTest extends SuccessUnit
{

    protected function buildStatement(Builder $builder, array $further): Statement
    {
        $data = [
            'a' => [
                '1.1', '1.2', '1.3',
            ],
            'b' => [
                '2.1', '2.2', '2.3',
            ],
        ];
        return $builder->build(
            $builder->foreach($data, $value, $key) .
            'toplevel' .
            $builder->value($key) .
            $builder->foreach($value, $subValue, $subKey) .
            $builder->value($subKey) . '=>' . $builder->value($subValue) .
            $builder->endforeach() .
            'toplevelend' .
            $builder->endforeach()
        );
    }

    protected function getStatement(): string
    {
        return 'toplevel{STR:"a"}{INT:0}=>{STR:"1.1"}{INT:1}=>{STR:"1.2"}{INT:2}=>{STR:"1.3"}toplevelend' .
            'toplevel{STR:"b"}{INT:0}=>{STR:"2.1"}{INT:1}=>{STR:"2.2"}{INT:2}=>{STR:"2.3"}toplevelend';
    }
}
