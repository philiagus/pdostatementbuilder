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

use Philiagus\PDOStatementBuilder\EvaluationControl;

abstract class AbstractToken
{
    private string $id;
    private int $uniqueCounter = 0;
    private string $uniqueId;

    /**
     * AbstractToken constructor.
     *
     * @param string $type
     */
    protected function __construct(string $type)
    {
        $this->uniqueId = spl_object_id($this) . '_' . bin2hex(pack('E', microtime(true)));
        $this->id = $this->uniqueId($type);
    }

    protected function uniqueId(string $type): string
    {
        return "\0\0\0" . $this->uniqueId . '_' . $this->uniqueCounter++ . "_$type\0\0\0";
    }

    /**
     * @param string $token
     * @param EvaluationControl $builderInteraction
     */
    abstract public function execute(string $token, EvaluationControl $builderInteraction);

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * Asserts that the specified token has been closed
     */
    public function assertClosed(): void
    {
    }
}
