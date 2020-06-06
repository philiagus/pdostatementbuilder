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

abstract class ErrorUnit extends Unit
{

    public function testUnit(Builder $builder, $further): void
    {
        $this->buildStatement($builder, $further);
    }

    abstract protected function buildStatement(Builder $builder, array $further): void;

    /**
     * @return string
     */
    public function expectedExceptionString(): string
    {
        return $this->getExceptionMessage();
    }

    abstract protected function getExceptionMessage(): string;

    /**
     * @return string
     */
    public function getExceptionClass(): string
    {
        return \LogicException::class;
    }
}