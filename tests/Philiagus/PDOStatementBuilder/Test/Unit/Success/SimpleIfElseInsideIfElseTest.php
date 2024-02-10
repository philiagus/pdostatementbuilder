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
use Philiagus\PDOStatementBuilder\Test\DataProvider;
use Philiagus\PDOStatementBuilder\Test\Unit\SuccessUnit;

class SimpleIfElseInsideIfElseTest extends SuccessUnit
{
    private $expected = null;

    public static function subCases(): array
    {
        $cases = [];
        foreach (DataProvider::any() as $ifName => $ifValue) {
            foreach (DataProvider::any() as $if2Name => $if2Value) {
                foreach (DataProvider::any() as $if3Name => $if3Value) {
                    $cases["$ifName / $if2Name / $if3Name"] = [
                        $ifValue,
                        $if2Value,
                        $if3Name,
                        (
                            $ifValue ?
                                '1.1' . ($if2Value ? '2.1' : '2.2') :
                                '1.2' . ($if3Value ? '3.1' : '3.2')
                        ),
                    ];
                }
            }
        }

        return $cases;
    }

    protected function buildStatement(Builder $builder, array $further): Statement
    {
        [$value, $value2, $value3, $this->expected] = $further;

        return $builder->build(
            "0 {$builder->if($value)}" .
            "1.1{$builder->if($value2)}2.1{$builder->else()}2.2{$builder->endif()}" .
            "{$builder->else()}" .
            "1.2{$builder->if($value3)}2.1{$builder->else()}2.2{$builder->endif()}" .
            "{$builder->endif()} 9"
        );
    }

    protected function getStatement(): string
    {
        return '0 ' . $this->expected . ' 9';
    }
}
