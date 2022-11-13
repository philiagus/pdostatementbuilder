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

namespace Philiagus\PDOStatementBuilder\Test\Unit\Token\Value;

use Philiagus\PDOStatementBuilder\Token\Value\AbstractBuilderValue;
use PHPUnit\Framework\TestCase;

class AbstractBuilderValueTest extends TestCase
{


    private function instance(): AbstractBuilderValue
    {
        return new class extends AbstractBuilderValue{
            public function resolveAsPDOStatementBuilderValue(): mixed
            {
                return 'ok';
            }
        };
    }

    public function testExceptionOnOffsetExists(): void
    {
        self::expectException(\LogicException::class);
        self::expectExceptionMessage('offset exists cannot be called on BuilderValues');
        isset($this->instance()['ay']);
    }

    public function testExceptionOnOffsetSet(): void
    {
        self::expectException(\LogicException::class);
        self::expectExceptionMessage('offset set cannot be called on BuilderValues');
        $this->instance()['ay'] = 'asdf';
    }

    public function testExceptionOnOffsetUnset(): void
    {
        self::expectException(\LogicException::class);
        self::expectExceptionMessage('offset unset cannot be called on BuilderValues');
        unset($this->instance()['ay']);
    }
}
