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
use Philiagus\PDOStatementBuilder\PDOStatementBuilderParameter;
use Philiagus\PDOStatementBuilder\Statement;
use Philiagus\PDOStatementBuilder\Test\Unit\SuccessUnit;

class ConvertedValueTest extends SuccessUnit
{
    protected function buildStatement(Builder $builder, array $further): Statement
    {
        $value = new class() implements PDOStatementBuilderParameter {
            public function toPDOStatementValue(?int &$type = null)
            {
                $type = \PDO::PARAM_NULL;

                return 'string';
            }
        };

        $value2 = new class() implements PDOStatementBuilderParameter {
            public function toPDOStatementValue(?int &$type = null)
            {
                return 1;
            }
        };

        return $builder->build(
            "PARAM {$builder->value($value)}{$builder->value($value2)} {$builder->in([$value, $value2])}"
        );
    }

    protected function getStatement(): string
    {
        return 'PARAM {NULL:"string"}{INT:1} {NULL:"string"}, {INT:1}';
    }
}
