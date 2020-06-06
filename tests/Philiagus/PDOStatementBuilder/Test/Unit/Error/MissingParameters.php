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

class MissingParameters extends ErrorUnit
{

    protected function getExceptionMessage(): string
    {
        return "Not all parameters bound by this builder have been used in the statement. Did you remove parts of the generated string?";
    }

    protected function buildStatement(Builder $builder, array $further): void
    {
        $builder->value(3);
        $builder->build($builder->value(1));
    }
}