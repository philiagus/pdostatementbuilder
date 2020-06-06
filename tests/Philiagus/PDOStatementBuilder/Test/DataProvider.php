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

class DataProvider
{

    public static function any(): array
    {
        return array_merge(self::truthy(), self::falsy());
    }

    public static function truthy(): array
    {
        return [
            'real true' => [true],
            'int 1' => [1],
            'float .4' => [.4],
            'object' => [new \stdClass()],
            'non empty array' => [['asdf']],
            'non empty string' => ['string'],
            'NAN' => [NAN],
            'INF' => [INF],
            '-INF' => [-INF],
        ];
    }

    public static function falsy(): array
    {
        return [
            'real false' => [false],
            'int 0' => [0],
            'float .0' => [.0],
            'null' => [null],
            'empty array' => [[]],
            'empty string' => [''],
        ];
    }

}