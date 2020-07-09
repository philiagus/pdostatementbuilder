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

class UnboundParametersTest extends ErrorUnit
{

    private $parameter = '';

    protected function getExceptionMessage(): string
    {
        $builder = new Builder();
        $this->parameter = $builder->value(4);

        return "An unknown parameter $this->parameter was found in the statement. Did you call prepare on a builder not used to bind the parameters?";
    }

    protected function buildStatement(Builder $builder, array $further): void
    {
        $builder->build($this->parameter);
    }
}