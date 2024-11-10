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

namespace Philiagus\PDOStatementBuilder;

final class Parameter
{

    /**
     * Parameter constructor.
     *
     * @param string|int $name
     * @param mixed $value
     * @param int $type
     */
    public function __construct(
        private string|int $name,
        private mixed $value,
        private int $type
    )
    {
    }

    /**
     * @return int
     */
    public function getType(): int
    {
        return $this->type;
    }

    /**
     * @return string|int
     */
    public function getName(): string|int
    {
        return $this->name;
    }

    /**
     * @return mixed
     */
    public function getValue(): mixed
    {
        return $this->value;
    }
}
