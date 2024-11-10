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

namespace Philiagus\PDOStatementBuilder\Test;

use Philiagus\PDOStatementBuilder\Builder;
use Philiagus\PDOStatementBuilder\Parameter;
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

    public function testSimpleVariants(): void
    {
        $statement = Builder::simple(
            '',
            [
                null,
                new Parameter(1, 'value', \PDO::PARAM_LOB),
                ':something' => 3
            ]
        );

        self::assertSame('', $statement->getStatement());
        $parameters = $statement->getParameters();
        self::assertCount(3, $parameters);
        self::assertSame([0, 1, 2], array_keys($parameters));
        [$p1, $p2, $p3] = $parameters;
        self::assertSame(0, $p1->getName());
        self::assertSame(null, $p1->getValue());
        self::assertSame(\PDO::PARAM_NULL, $p1->getType());

        self::assertSame(1, $p2->getName());
        self::assertSame('value', $p2->getValue());
        self::assertSame(\PDO::PARAM_LOB, $p2->getType());

        self::assertSame(':something', $p3->getName());
        self::assertSame(3, $p3->getValue());
        self::assertSame(\PDO::PARAM_INT, $p3->getType());
    }

}
