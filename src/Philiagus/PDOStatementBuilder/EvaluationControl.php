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

namespace Philiagus\PDOStatementBuilder;

class EvaluationControl
{
    private \Closure $goto;
    private \Closure $injectValue;
    private \Closure $injectIn;
    private \Closure $continue;
    private \Closure $injectRaw;

    public function __construct(
        \Closure $goto,
        \Closure $continue,
        \Closure $injectValue,
        \Closure $injectIn,
        \Closure $injectRaw
    )
    {
        $this->goto = $goto;
        $this->continue = $continue;
        $this->injectValue = $injectValue;
        $this->injectIn = $injectIn;
        $this->injectRaw = $injectRaw;
    }

    public function goto(string $token): self
    {
        ($this->goto)($token);

        return $this;
    }

    public function injectValue($value, ?int $type): self
    {
        ($this->injectValue)($value, $type);

        return $this;
    }

    public function injectIn($data, ?int $type, $emptyFallback): self
    {
        ($this->injectIn)($data, $type, $emptyFallback);

        return $this;
    }

    public function continue(): self
    {
        ($this->continue)();

        return $this;
    }

    public function injectRaw(string $raw): self
    {
        ($this->injectRaw)($raw);

        return $this;
    }
}
