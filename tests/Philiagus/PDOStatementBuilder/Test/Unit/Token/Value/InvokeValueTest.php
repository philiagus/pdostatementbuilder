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
use Philiagus\PDOStatementBuilder\Token\Value\InvokeValue;
use PHPUnit\Framework\TestCase;

class InvokeValueTest extends TestCase
{

    public function provideOffset(): array
    {
        $generated = new class() implements BuilderValue {
            public function resolveAsPDOStatementBuilderValue(): mixed
            {
                return 1;
            }
        };

        return [
            'fixed' => [1, 1],
            'generated' => [$generated, $generated],
            'mixed' => [1, $generated],
        ];
    }

    /**
     * @dataProvider provideOffset
     *
     * @param $arg1
     * @param $arg2
     */
    public function testSuccess($arg1, $arg2): void
    {
        $args = null;
        $object = new class($args) {
            private $args;

            public function __construct(&$args)
            {
                $this->args = &$args;
            }

            public function __invoke(...$args)
            {
                $this->args = $args;

                return 'result';
            }
        };

        $protoValue = new class($object) implements BuilderValue {

            private $object;

            public function __construct($object)
            {
                $this->object = $object;
            }

            public function resolveAsPDOStatementBuilderValue(): mixed
            {
                return $this->object;
            }
        };

        $value = new InvokeValue($protoValue, [$arg1, $arg2]);
        self::assertSame('result', $value->resolveAsPDOStatementBuilderValue());

        self::assertSame([1, 1], $args);
    }

}
