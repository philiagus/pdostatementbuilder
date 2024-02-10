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

class ForeachStatementAllTokenTypesTest extends SuccessUnit
{

    protected function buildStatement(Builder $builder, array $further): Statement
    {
        $array = [
            'key' => [function () {
                return new class() {
                    public function method($arg)
                    {
                        return $arg;
                    }
                };
            }],
        ];

        return $builder->build(
            "a " .
            $builder->foreach($array, $v1) .
            $builder->foreach($array, $v2, $k2) .
            $builder->raw($v2[0]()->method($v1[0]()->method((object)['a' => 'argument'])->a)) .
            $builder->endforeach() .
            "{$builder->endforeach()} b"
        );
    }

    protected function getStatement(): string
    {
        return 'a argument b';
    }
}
