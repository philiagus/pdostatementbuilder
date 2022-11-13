<?php
/*
 * This file is part of philiagus/pdostatementbuilder
 *
 * (c) Andreas Bittner <philiagus@philiagus.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Philiagus\PDOStatementBuilder\Test\Unit\Token\Value;

use Philiagus\PDOStatementBuilder\Token\Value\ForeachInfoValue;
use PHPUnit\Framework\TestCase;

class ForeachInfoValueTest extends TestCase
{

    public function testResolveAsPDOStatementBuilderValue()
    {
        $token = new ForeachInfoValue();
        self::expectException(\LogicException::class);
        $token->resolveAsPDOStatementBuilderValue();
    }
}
