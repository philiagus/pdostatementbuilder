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

use Philiagus\PDOStatementBuilder\Builder;
use PHPUnit\Framework\TestCase;

/**
 * Class PDOStatementBuilderTest
 *
 * @package Philiagus\PDOStatementBuilder\Test
 * @covers \Philiagus\PDOStatementBuilder\Builder
 */
class BuilderTest extends TestCase
{

    public function testTransformValueTypeError(): void
    {
        self::expectException(\LogicException::class);
        self::expectExceptionMessage('transformValue transformed type to be neither null nor integer');
        $builder = new class() extends Builder {
            protected static function transformValue(mixed $value, ?int &$type): mixed
            {
                $type = 'asdf';

                return $value;
            }
        };

        $builder->build($builder->value('test'));
    }

    public function testStaticSimpleTransformValueTypeError(): void
    {
        self::expectException(\LogicException::class);
        self::expectExceptionMessage('transformValue transformed type to be neither null nor integer');
        $builder = new class() extends Builder {
            protected static function transformValue(mixed $value, ?int &$type): mixed
            {
                $type = 'asdf';

                return $value;
            }
        };

        $builder::simple('', [
            ':something' => 1
        ]);
    }

}
