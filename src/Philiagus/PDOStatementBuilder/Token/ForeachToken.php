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
use Philiagus\PDOStatementBuilder\Token\Value\ForeachInfoValue;
use Philiagus\PDOStatementBuilder\Token\Value\WritableValue;

class ForeachToken extends AbstractToken
{

    private ?string $endforeach = null;
    private mixed $iterable;
    private WritableValue $value;
    private WritableValue $key;
    private ForeachInfoValue $info;
    private int $dataPointer = 0;
    private int $dataPointerCount = 0;

    /** @var array{0: mixed, 1: mixed} */
    private array $dataAsArray = [];

    public function __construct(
        $iterable,
        &$value,
        &$key,
        &$info,
        private ?\Closure $closure
    )
    {
        parent::__construct('foreach');
        $this->iterable = $iterable;
        if (!($value instanceof WritableValue)) {
            $value = new WritableValue();
        }
        if (!($key instanceof WritableValue)) {
            $key = new WritableValue();
        }
        if(!($info instanceof ForeachInfoValue)) {
            $info = new ForeachInfoValue();
        }
        $this->info = $info;
        $this->value = $value;
        $this->key = $key;
    }

    public function execute(string $token, EvaluationControl $builderInteraction)
    {
        // check that we are at the foreach token
        if ($token === $this->getId()) {
            $this->dataAsArray = [];
            $this->dataPointer = 0;
            if ($this->iterable instanceof BuilderValue) {
                $value = $this->iterable->resolveAsPDOStatementBuilderValue();
            } else {
                $value = $this->iterable;
            }

            if($this->closure !== null) {
                $value = ($this->closure)($value);
            }

            if (!is_iterable($value)) {
                throw new \LogicException('The argument provided to foreach must be iterable');
            }
            foreach ($value as $k => $v) {
                $this->dataAsArray[] = [$k, $v];
            }
            $this->dataPointerCount = count($this->dataAsArray);
            if ($this->dataPointerCount === 0) {
                // empty foreach -> move to endforeach
                $builderInteraction->goto($this->endforeach)->continue();

                return;
            }
            $this->nextValue();
            $builderInteraction->continue();

            return;
        }

        // we are at the endforeach token
        if ($this->dataPointer >= $this->dataPointerCount) {
            // each element has been iterated, reset data for memory usage
            $this->dataAsArray = [];
            $this->dataPointerCount = 0;
            $this->dataPointer = 0;
            $builderInteraction->continue();

            return;
        }

        // activate next value and move back to loop head
        $this->nextValue();
        $builderInteraction->goto($this->getId())->continue();
    }

    private function nextValue(): void
    {
        [$key, $value] = $this->dataAsArray[$this->dataPointer];
        $this->key->setPDOStatementBuilderValue($key);
        $this->value->setPDOStatementBuilderValue($value);
        $this->info->setPDOStatementBuilderValue($this->dataPointer, $this->dataPointerCount);
        $this->dataPointer++;
    }

    public function assertClosed(): void
    {
        if ($this->endforeach === null) {
            throw new \LogicException('Unclosed foreach detected');
        }
    }

    public function end(): string
    {
        $this->endforeach = $this->uniqueId('endforeach');

        return $this->endforeach;
    }
}
