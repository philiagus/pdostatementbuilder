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

use Philiagus\PDOStatementBuilder\BuilderValue;
use Philiagus\PDOStatementBuilder\Token\Value\ArrayAccessValue;
use PHPUnit\Framework\TestCase;

class ArrayAccessValueTest extends TestCase
{

    public function provideOffset(): array
    {
        return [
            'fixed' => [1],
            'generated' => [new class() implements BuilderValue {
                public function resolveAsPDOStatementBuilderValue()
                {
                    return 1;
                }
            }],
        ];
    }

    /**
     * @param $offset
     *
     * @dataProvider provideOffset
     */
    public function testSuccess($offset): void
    {
        $value = new ArrayAccessValue(
            new class() implements BuilderValue {
                public function resolveAsPDOStatementBuilderValue()
                {
                    return ['a', 'b', 'c'];
                }
            },
            $offset
        );
        self::assertSame('b', $value->resolveAsPDOStatementBuilderValue());
    }

}