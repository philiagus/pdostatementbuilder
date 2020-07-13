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
use Philiagus\PDOStatementBuilder\Token\Value\WritableValue;

class ForeachToken extends AbstractToken
{

    /**
     * @var null|string
     */
    private $endforeach = null;

    /**
     * @var mixed
     */
    private $iterable;

    /**
     * @var WritableValue
     */
    private $value;

    /**
     * @var WritableValue
     */
    private $key;

    /**
     * @var array
     */
    private $dataAsArray = [];

    public function __construct($iterable, &$value, &$key)
    {
        parent::__construct('foreach');
        $this->iterable = $iterable;
        if (!($value instanceof WritableValue)) {
            $value = new WritableValue();
        }
        if (!($key instanceof WritableValue)) {
            $key = new WritableValue();
        }
        $this->value = $value;
        $this->key = $key;
    }

    public function execute(string $token, EvaluationControl $builderInteraction)
    {
        if ($token === $this->getId()) {
            $this->dataAsArray = [];
            if ($this->iterable instanceof BuilderValue) {
                $value = $this->iterable->get();
            } else {
                $value = $this->iterable;
            }
            if (!is_iterable($value)) {
                throw new \LogicException('The argument provided to foreach must be iterable');
            }
            foreach ($value as $k => $v) {
                $this->dataAsArray[] = [$k, $v];
            }
            if (empty($this->dataAsArray)) {
                $builderInteraction->goto($this->endforeach)->continue();

                return;
            }
            [$k, $v] = array_shift($this->dataAsArray);
            $this->key->set($k);
            $this->value->set($v);
            $builderInteraction->continue();

            return;
        }

        if (empty($this->dataAsArray)) {
            $builderInteraction->continue();

            return;
        }

        [$k, $v] = array_shift($this->dataAsArray);
        $this->key->set($k);
        $this->value->set($v);
        $builderInteraction->goto($this->getId())->continue();
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