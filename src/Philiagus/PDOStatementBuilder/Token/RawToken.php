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

namespace Philiagus\PDOStatementBuilder\Token;

use Philiagus\PDOStatementBuilder\BuilderValue;
use Philiagus\PDOStatementBuilder\EvaluationControl;

class RawToken extends AbstractToken
{
    public function __construct(
        private mixed $value,
        private ?\Closure $closure = null
    )
    {
        parent::__construct('value');
    }

    public function execute(string $token, EvaluationControl $builderInteraction): void
    {
        if ($this->value instanceof BuilderValue) {
            $value = $this->value->resolveAsPDOStatementBuilderValue();
        } else {
            $value = $this->value;
        }

        if($this->closure !== null) {
            $value = ($this->closure)($value);
        }

        $builderInteraction
            ->injectRaw((string) $value)
            ->continue();
    }
}
