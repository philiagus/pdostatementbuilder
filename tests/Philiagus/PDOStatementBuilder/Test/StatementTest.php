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
use Philiagus\PDOStatementBuilder\Statement;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;

class StatementTest extends TestCase
{
    use ProphecyTrait;

    public function testSymmetry(): void
    {
        $statement = new Statement(
            'STRING',
            []
        );

        self::assertSame('STRING', $statement->getStatement());
        self::assertSame([], $statement->getParameters());
    }

    public function testParameterAssertion(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Statement(
            'STRING',
            ['a']
        );
    }

    public function testPrepare(): void
    {
        $string = 'ASDF';
        $parameters = [
            new Parameter('a', 'a', 1),
            new Parameter('b', 4, 2),
        ];
        $statement = new Statement(
            $string,
            $parameters
        );

        $PDStatement = $this->prophesize(\PDOStatement::class);
        foreach ($parameters as $parameter) {
            $PDStatement
                ->bindValue($parameter->getName(), $parameter->getValue(), $parameter->getType())
                ->shouldBeCalledOnce()
                ->willReturn(true);
        }
        $PDStatement = $PDStatement->reveal();

        $pdo = $this->prophesize(\PDO::class);
        $pdo->prepare($string)->shouldBeCalledOnce()->willReturn(
            $PDStatement
        );
        $pdo = $pdo->reveal();

        self::assertSame($PDStatement, $statement->prepare($pdo));
    }

    public function testPrepareException(): void
    {
        $string = 'ASDF';
        $parameters = [
            new Parameter('a', 'a', 1),
            new Parameter('b', 4, 2),
        ];
        $statement = new Statement(
            $string,
            $parameters
        );

        $pdo = $this->prophesize(\PDO::class);
        $pdo->prepare($string)->shouldBeCalledOnce()->willReturn(false);
        $pdo = $pdo->reveal();

        $this->expectException(\Exception::class);
        $statement->prepare($pdo);
    }

    public function testPrepareBindException(): void
    {
        $string = 'ASDF';
        $parameters = [
            new Parameter('a', 'a', 1),
            new Parameter('b', 4, 2),
        ];
        $statement = new Statement(
            $string,
            $parameters
        );

        $PDStatement = $this->prophesize(\PDOStatement::class);
        $PDStatement->bindValue('a', 'a', 1)->willReturn(false);
        $PDStatement = $PDStatement->reveal();

        $pdo = $this->prophesize(\PDO::class);
        $pdo->prepare($string)->shouldBeCalledOnce()->willReturn(
            $PDStatement
        );
        $pdo = $pdo->reveal();

        $this->expectException(\Exception::class);
        $statement->prepare($pdo);
    }
}
