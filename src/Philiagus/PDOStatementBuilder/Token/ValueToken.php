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

class ValueToken extends AbstractToken
{
    /**
     * @var mixed
     */
    private $value;

    /**
     * @var int|null
     */
    private $type;

    public function __construct($value, ?int $type)
    {
        parent::__construct('value');
        $this->value = $value;
        $this->type = $type;
    }

    public function execute(string $token, EvaluationControl $builderInteraction): void
    {
        if ($this->value instanceof BuilderValue) {
            $value = $this->value->get();
        } else {
            $value = $this->value;
        }
        $builderInteraction
            ->injectValue($value, $this->type)
            ->continue();
    }
}