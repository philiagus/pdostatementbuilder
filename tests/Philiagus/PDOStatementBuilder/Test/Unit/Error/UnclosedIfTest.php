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

namespace Philiagus\PDOStatementBuilder\Test\Unit\Error;

use Philiagus\PDOStatementBuilder\Builder;
use Philiagus\PDOStatementBuilder\Test\Unit\ErrorUnit;

class UnclosedIfTest extends ErrorUnit
{

    protected function getExceptionMessage(): string
    {
        return 'Unclosed if detected';
    }

    protected function buildStatement(Builder $builder, array $further): void
    {
        $builder->build("{$builder->if(1)}");
    }
}
