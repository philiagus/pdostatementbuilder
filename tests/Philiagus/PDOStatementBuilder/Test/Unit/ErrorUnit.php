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

namespace Philiagus\PDOStatementBuilder\Test\Unit;

use Philiagus\PDOStatementBuilder\Builder;
use PHPUnit\Framework\TestCase;

abstract class ErrorUnit extends TestCase
{

    public function testUnit(): void
    {
        $this->expectException($this->getExceptionClass());
        $this->expectExceptionMessage($this->expectedExceptionString());
        $this->buildStatement(new Builder(), []);
    }

    /**
     * @return string
     */
    public function getExceptionClass(): string
    {
        return \LogicException::class;
    }

    /**
     * @return string
     */
    public function expectedExceptionString(): string
    {
        return $this->getExceptionMessage();
    }

    abstract protected function getExceptionMessage(): string;

    abstract protected function buildStatement(Builder $builder, array $further): void;
}
