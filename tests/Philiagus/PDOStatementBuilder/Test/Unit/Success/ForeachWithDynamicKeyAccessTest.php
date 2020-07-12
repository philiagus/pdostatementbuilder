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

class ForeachWithDynamicKeyAccessTest extends SuccessUnit
{

    protected function buildStatement(Builder $builder, array $further): Statement
    {
        $data = [
            [
                '1.1', '1.2', '1.3',
            ],
            [
                '2.1', '2.2', '2.3',
            ],
        ];
        return $builder->build(
            $builder->foreach($data, $value, $key) .
            'toplevel' .
            $builder->value($key) .
            $builder->value($value[$key]) .
            'toplevelend' .
            $builder->endforeach()
        );
    }

    protected function getStatement(): string
    {
        return 'toplevel{INT:0}{STR:"1.1"}toplevelend' .
            'toplevel{INT:1}{STR:"2.2"}toplevelend';
    }
}