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
use Philiagus\PDOStatementBuilder\ReplacementMarker;
use Philiagus\PDOStatementBuilder\Test\Unit\ErrorUnit;

class ReplacementMarkerOutsideLoopValueTest extends ErrorUnit
{

    protected function getExceptionMessage(): string
    {
        return 'Using a replacement marker outside of the corresponding structure';
    }

    protected function buildStatement(Builder $builder, array $further): void
    {
        $builder->value(new ReplacementMarker());
    }
}