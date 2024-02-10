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

namespace Philiagus\PDOStatementBuilder\Token\Value;

use Philiagus\PDOStatementBuilder\BuilderValue;

/**
 * @property-read int $count
 * @property-read bool $first
 * @property-read bool $last
 * @property-read int $index
 */
class ForeachInfoValue implements BuilderValue
{

    private WritableValue $indexValue;
    private WritableValue $firstValue;
    private WritableValue $lastValue;
    private WritableValue $countValue;

    public function __construct()
    {
        $this->indexValue = new WritableValue();
        $this->firstValue = new WritableValue();
        $this->lastValue = new WritableValue();
        $this->countValue = new WritableValue();
    }

    /**
     * @param int $index
     * @param int $count
     *
     * @return void
     */
    public function setPDOStatementBuilderValue(int $index, int $count): void
    {
        $this->indexValue->setPDOStatementBuilderValue($index);
        $this->countValue->setPDOStatementBuilderValue($count);
        $this->firstValue->setPDOStatementBuilderValue($index === 0);
        $this->lastValue->setPDOStatementBuilderValue($count === $index + 1);
    }

    public function __get(string $field): BuilderValue
    {
        return match ($field) {
            'first' => $this->firstValue,
            'last' => $this->lastValue,
            'count' => $this->countValue,
            'index' => $this->indexValue,
            default => throw new \OutOfBoundsException("The field '$field' is not provided by the foreach info")
        };
    }

    /**
     * @inheritDoc
     */
    public function resolveAsPDOStatementBuilderValue(): mixed
    {
        throw new \LogicException(
            'The $info value of $builder->foreach cannot be used as a parameter. Please us the public properties instead'
        );
    }
}
