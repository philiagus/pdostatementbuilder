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

class DuplicateParameterNameTest extends ErrorUnit
{

    protected function getExceptionMessage(): string
    {
        return 'Sub-Statement error: Parameter :param would be bound twice';
    }

    protected function buildStatement(Builder $builder, array $further): void
    {
        $statement = Builder::simple('', [':param' => 1]);
        $builder->build(
            $builder->in($statement) . $builder->in($statement)
        );
    }
}