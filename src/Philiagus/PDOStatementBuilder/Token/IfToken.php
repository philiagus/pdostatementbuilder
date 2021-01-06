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

class IfToken extends AbstractToken
{

    /**
     * @var array<string,mixed[]>
     */
    private $idToTruthy = [];

    /**
     * @var null|string
     */
    private $else = null;

    /**
     * @var null|string
     */
    private $endif = null;

    private $hasExecuted = false;

    public function __construct($truthy, ?callable $conversion)
    {
        parent::__construct('if');
        $this->idToTruthy[$this->getId()] = [
            $truthy,
            $conversion
        ];
    }

    public function execute(string $token, EvaluationControl $builderInteraction): void
    {
        if ($token === $this->getId()) {
            $this->hasExecuted = false;
        }

        if ($this->hasExecuted) {
            $builderInteraction->goto($this->endif)->continue();

            return;
        }

        foreach ($this->idToTruthy as $id => [$truthy, $conversion]) {
            if($truthy instanceof BuilderValue) {
                $truthyValue = $truthy->resolveAsPDOStatementBuilderValue();
            } else {
                $truthyValue = $truthy;
            }
            if($conversion !== null) {
                $truthyValue = $conversion($truthyValue);
            }
            if ($truthyValue) {
                $this->hasExecuted = true;
                $builderInteraction->goto($id)->continue();

                return;
            }
        }

        $builderInteraction->goto($this->endif)->continue();
    }

    public function elseif($truthy, ?callable $conversion): string
    {
        if ($this->else !== null) {
            throw new \LogicException('Trying to define elseif for if which already has an else defined, expected endif');
        }
        $id = $this->uniqueId('elseif');
        $this->idToTruthy[$id] = [$truthy, $conversion];

        return $id;
    }

    public function else(): string
    {
        if ($this->else !== null) {
            throw new \LogicException('Trying to define else for if which already has an else defined, expected endif');
        }
        $id = $this->uniqueId('else');
        $this->else = $id;
        $this->idToTruthy[$id] = [true, null];

        return $id;
    }

    public function endif(): string
    {
        $id = $this->uniqueId('endif');
        $this->endif = $id;

        return $id;
    }

    public function assertClosed(): void
    {
        if ($this->endif === null) {
            throw new \LogicException('Unclosed if detected');
        }
    }
}