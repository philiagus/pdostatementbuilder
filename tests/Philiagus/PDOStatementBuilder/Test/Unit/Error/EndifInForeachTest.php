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

namespace Philiagus\PDOStatementBuilder\Test\Unit\Error;

use Philiagus\PDOStatementBuilder\Builder;
use Philiagus\PDOStatementBuilder\Test\Unit\ErrorUnit;

class EndifInForeachTest extends ErrorUnit
{

    protected function getExceptionMessage(): string
    {
        return 'Trying to create endif outside if structure';
    }

    protected function buildStatement(Builder $builder, array $further): void
    {
        $builder->foreach(false, $a, $b);
        $builder->endif();
    }
}
