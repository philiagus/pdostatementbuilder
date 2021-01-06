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

use Philiagus\PDOStatementBuilder\Test\DataProvider;
use Philiagus\PDOStatementBuilder\Token\Value\WritableValue;
use PHPUnit\Framework\TestCase;

class WritableValueTest extends TestCase
{

    public function provideValues(): array
    {
        return DataProvider::any();
    }

    /**
     * @param $v
     *
     * @dataProvider provideValues
     */
    public function testSuccess($v): void
    {
        $value = new WritableValue();
        $value->setPDOStatementBuilderValue($v);
        if(is_float($v) && is_nan($v)) {
            self::assertNan($value->resolveAsPDOStatementBuilderValue());
        } else {
            self::assertSame($v, $value->resolveAsPDOStatementBuilderValue());
        }
    }

    public function testUnsetException(): void
    {
        $value = new WritableValue();
        $this->expectException(\LogicException::class);
        $value->resolveAsPDOStatementBuilderValue();
    }

}