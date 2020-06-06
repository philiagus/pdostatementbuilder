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

namespace Philiagus\PDOStatementBuilder\Test\Unit;

use Philiagus\PDOStatementBuilder\Builder;
use Philiagus\PDOStatementBuilder\Statement;
use PHPUnit\Framework\Assert;

abstract class SuccessUnit extends Unit
{

    /**
     * @param Builder $builder
     *
     * @param array $further
     *
     * @dataProvider provideBuilders
     */
    public function testUnit(Builder $builder, $further): void
    {
        $statement = $this->buildStatement($builder, $further);

        $expectedStatement = $this->getStatement();
        if (preg_match_all('~{(?<type>[A-Z]+):(?<value>[^}]+)}~', $expectedStatement, $matches)) {
            $pdoConstants = $this->getPDOConstantMapping();


            $regexExpectedStatement = preg_quote($expectedStatement);
            $expectedParameters = [];
            foreach ($matches[0] as $index => $fullPlaceholder) {
                $type = $matches['type'][$index];
                $value = $matches['value'][$index];
                $trueValue = eval("return $value;");
                $regexExpectedStatement = preg_replace(
                    '/' . preg_quote(preg_quote($fullPlaceholder)) . '/',
                    '(:p\d+_[0-9a-f]+_\d+p)',
                    $regexExpectedStatement,
                    1,
                    $count
                );
                Assert::assertSame(1, $count, 'Statement regex could not be prepared');
                $expectedParameters[] = [$trueValue, $pdoConstants[$type]];
            }

            $regexExpectedStatement = "/^$regexExpectedStatement$/";
            Assert::assertRegExp($regexExpectedStatement, $statement->getStatement());
            preg_match($regexExpectedStatement, $statement->getStatement(), $matches);

            $parameters = [];
            foreach ($statement->getParameters() as $parameter) {
                $parameters[$parameter->getName()] = $parameter;
            }

            foreach (array_slice($matches, 1) as $index => $parameterName) {
                Assert::assertArrayHasKey($parameterName, $parameters, "Parameter $index was not bound in the Statement");
                Assert::assertArrayHasKey($index, $expectedParameters, "More parameters are bound than expected");
                $parameter = $parameters[$parameterName];
                Assert::assertSame($expectedParameters[$index][0], $parameter->getValue());
                Assert::assertSame($expectedParameters[$index][1], $parameter->getType());
            }
        } else {
            Assert::assertSame($expectedStatement, $statement->getStatement());
            Assert::assertEmpty($statement->getParameters());
        }
    }

    abstract protected function buildStatement(Builder $builder, array $further): Statement;

    abstract protected function getStatement(): string;

    /**
     * @return array
     */
    private function getPDOConstantMapping(): array
    {
        static $constants = null;
        if ($constants === null) {
            $found = [];
            $reflection = new \ReflectionClass(\PDO::class);
            foreach ($reflection->getConstants() as $name => $value) {
                if (preg_match('/^PARAM_/', $name)) {
                    $found[substr($name, strlen('PARAM_'))] = $value;
                }
            }

            $constants = $found;
        }

        return $constants;
    }

}