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

use Philiagus\PDOStatementBuilder\BuilderValue;

class ArrayAccessValue extends AbstractBuilderValue
{

    /**
     * @var BuilderValue
     */
    private $source;

    /**
     * @var mixed
     */
    private $offset;

    public function __construct(BuilderValue $source, $offset)
    {
        $this->source = $source;
        $this->offset = $offset;
    }

    public function resolveAsPDOStatementBuilderValue()
    {
        if ($this->offset instanceof BuilderValue) {
            $offset = $this->offset->resolveAsPDOStatementBuilderValue();
        } else {
            $offset = $this->offset;
        }

        return $this->source->resolveAsPDOStatementBuilderValue()[$offset];
    }
}