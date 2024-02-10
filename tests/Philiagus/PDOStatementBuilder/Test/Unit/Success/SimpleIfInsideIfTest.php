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

class SimpleIfInsideIfTest extends SuccessUnit
{

    private $expected = null;

    public static function subCases(): array
    {
        $cases = [];
        foreach (DataProvider::any() as $ifName => $ifValue) {
            foreach (DataProvider::any() as $if2Name => $if2Value) {
                $cases["$ifName / $if2Name"] = [
                    $ifValue,
                    $if2Value,
                    ($ifValue ? '1' . ($if2Value ? '2' : '') : ''),
                ];
            }
        }

        return $cases;
    }

    protected function buildStatement(Builder $builder, array $further): Statement
    {
        [$value, $value2, $this->expected] = $further;

        return $builder->build(
            "0 {$builder->if($value)}1{$builder->if($value2)}2{$builder->endif()}{$builder->endif()} 9"
        );
    }

    protected function getStatement(): string
    {
        return '0 ' . $this->expected . ' 9';
    }
}
