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

use Philiagus\PDOStatementBuilder\ReplacementMarker;
use PHPUnit\Framework\TestCase;

class ReplacementMarkerTest extends TestCase
{

    public function testSymmetry(): void
    {
        $marker = new ReplacementMarker();
        $marker->addIn('a', 1, 'b');
        $marker->addIn('c', 2, 'd');
        $marker->addIn('e', null, 'f');

        $marker->addValue('g', null);
        $marker->addValue('h', 2);

        self::assertSame([
            ['a', 1, 'b'],
            ['c', 2, 'd'],
            ['e', null, 'f'],
        ], $marker->getIn());

        self::assertSame([
            ['g', null],
            ['h', 2],
        ], $marker->getValues()
        );

        self::assertIsString($marker->getToken());
    }

}
