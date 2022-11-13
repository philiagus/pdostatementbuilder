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
use Philiagus\PDOStatementBuilder\Test\Unit\SuccessUnit;

class ValueTest extends SuccessUnit
{
    protected function buildStatement(Builder $builder, array $further): Statement
    {
        return $builder->build(
            $builder->value('a')
        );
    }

    protected function getStatement(): string
    {
        return '{STR:"a"}';
    }
}
