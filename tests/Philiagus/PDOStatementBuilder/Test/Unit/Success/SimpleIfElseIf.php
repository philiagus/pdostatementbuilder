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
use Philiagus\PDOStatementBuilder\Test\DataProvider;
use Philiagus\PDOStatementBuilder\Test\Unit\SuccessUnit;

class SimpleIfElseIf extends SuccessUnit
{

    private $expected = null;

    public function subCases(): array
    {
        $cases = [];
        foreach (DataProvider::any() as $ifName => $ifValue) {
            foreach (DataProvider::any() as $elseIfName => $elseIfValue) {
                $cases["$ifName / $elseIfName"] = [
                    $ifValue,
                    $elseIfValue,
                    $ifValue ? 1 : ($elseIfValue ? 2 : ''),
                ];
            }
        }

        return $cases;
    }

    protected function buildStatement(Builder $builder, array $further): Statement
    {
        [$if, $elseIf, $this->expected] = $further;

        return $builder->build(
            "0{$builder->if($if)}1{$builder->elseif($elseIf)}2{$builder->endif()}9"
        );
    }

    protected function getStatement(): string
    {
        return '0' . $this->expected . '9';
    }
}