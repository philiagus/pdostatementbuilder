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
use Philiagus\PDOStatementBuilder\Test\Unit\SuccessUnit;
use Philiagus\PDOStatementBuilder\Test\Unit\Unit;
use PHPUnit\Framework\TestCase;

/**
 * Class AllUnitsTest
 *
 * @package Philiagus\PDOStatementBuilder\Test
 * @covers \Philiagus\PDOStatementBuilder\Builder
 */
class AllUnitsTest extends TestCase
{

    public function provider(): array
    {
        foreach (glob(__DIR__ . '/Unit/**/*.php') as $file) {
            require_once $file;
        }

        $cases = [];
        $unitNamespace = 'Philiagus\PDOStatementBuilder\Test\Unit\\';
        foreach (get_declared_classes() as $class) {
            if (strpos($class, $unitNamespace) !== 0) continue;
            $reflection = new \ReflectionClass($class);
            if (!$reflection->isInstantiable()) continue;
            /** @var SuccessUnit $instance */
            $instance = new $class();
            $furtherCases = $instance->subCases();
            $caseClassName = substr($class, strlen($unitNamespace));
            if (empty($furtherCases)) {
                $cases["$caseClassName"] = [$instance, new Builder(), []];
            } else {
                foreach ($furtherCases as $furtherName => $further) {
                    if (!is_array($further)) {
                        $further = [$further];
                    }
                    $cases["$caseClassName | $furtherName"] = [$instance, new Builder(), $further];
                }
            }
        }

        return $cases;
    }

    /**
     * @param Unit $instance
     * @param Builder $builder
     *
     * @param array $further
     *
     * @dataProvider  provider
     */
    public function testUnit(Unit $instance, Builder $builder, array $further): void
    {
        $exceptionString = $instance->expectedExceptionString();
        if ($exceptionString !== null) {
            $this->expectException($instance->getExceptionClass());
            $this->expectExceptionMessage($exceptionString);
        }
        $instance->testUnit($builder, $further);
    }

}