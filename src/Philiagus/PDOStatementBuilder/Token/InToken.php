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

class InToken extends AbstractToken
{

    private $data;
    /**
     * @var int|null
     */
    private $type;
    private $emptyFallback;

    public function __construct($data, ?int $type, $emptyFallback)
    {
        parent::__construct('in');

        $this->data = $data;
        $this->type = $type;
        $this->emptyFallback = $emptyFallback;
    }

    public function execute(string $token, EvaluationControl $builderInteraction): void
    {
        if ($this->data instanceof BuilderValue) {
            $value = $this->data->resolveAsPDOStatementBuilderValue();
        } else {
            $value = $this->data;
        }
        $builderInteraction
            ->injectIn($value, $this->type, $this->emptyFallback)
            ->continue();
    }
}