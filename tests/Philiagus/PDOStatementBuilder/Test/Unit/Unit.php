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

namespace Philiagus\PDOStatementBuilder\Test\Unit;

use Philiagus\PDOStatementBuilder\Builder;

abstract class Unit
{

    public function subCases(): array
    {
        return [];
    }

    abstract public function testUnit(Builder $builder, $further): void;

    public function expectedExceptionString(): ?string
    {
        return null;
    }

    public function getExceptionClass(): ?string
    {
        return null;
    }


}