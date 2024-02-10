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

namespace Philiagus\PDOStatementBuilder\Test\Unit\Success;

use Philiagus\PDOStatementBuilder\Builder;
use Philiagus\PDOStatementBuilder\Statement;
use Philiagus\PDOStatementBuilder\Test\Unit\SuccessUnit;

class InStatementFallbackWithTypeAndSpecificationTest extends SuccessUnit
{


    protected function buildStatement(Builder $builder, array $further): Statement
    {
        return $builder->build(
            "PARAM {$builder->in([], \PDO::PARAM_STR, 3)}"
        );
    }

    protected function getStatement(): string
    {
        return 'PARAM SELECT 0,0,0 FROM (SELECT 0 FROM dual) `noResultSubSelect` WHERE 0';
    }
}
