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

namespace Philiagus\PDOStatementBuilder\Test;

use Philiagus\PDOStatementBuilder\Parameter;
use PHPUnit\Framework\TestCase;

class ParameterTest extends TestCase
{


    public function testSymmetry(): void
    {
        $instance = new Parameter('name', INF, 1);
        self::assertSame('name', $instance->getName());
        self::assertInfinite($instance->getValue());
        self::assertSame(1, $instance->getType());
    }

}
