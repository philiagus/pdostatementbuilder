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

class DuplicateTokensTest extends ErrorUnit
{

    protected function getExceptionMessage(): string
    {
        return 'The tokens in the statement do not match the expected tokens. Did you temper with the generated string?';
    }

    protected function buildStatement(Builder $builder, array $further): void
    {
        $builder->build(
            str_repeat("{$builder->if(1)}{$builder->endif()}", 2)
        );
    }
}
