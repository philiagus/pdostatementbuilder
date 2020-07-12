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

namespace Philiagus\PDOStatementBuilder\Token\Value;

class WritableValue extends AbstractBuilderValue
{

    private $value = null;

    public function __construct()
    {
    }

    public function get()
    {
        return $this->value;
    }

    public function set($value): self
    {
        $this->value = $value;

        return $this;
    }

}