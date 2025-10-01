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

class ForeachWithSeparatorTest extends SuccessUnit
{
    protected function buildStatement(Builder $builder, array $further): Statement
    {
        $data = [1, 'one'];

        return $builder->build(
            $builder->foreach($data, $value, $key) .
                $builder->value($key) .
                $builder->value($value) .
                $builder->endforeach(', ')
        );
    }

    protected function getStatement(): string
    {
        return '{INT:0}{INT:1}, {INT:1}{STR:"one"}';
    }
}
