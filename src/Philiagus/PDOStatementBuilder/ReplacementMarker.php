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

class ReplacementMarker
{
    /**
     * @var string
     */
    private $token;

    /**
     * @var array
     */
    private $in = [];

    /**
     * @var array
     */
    private $values = [];

    public function __construct()
    {
        $this->token = "\0\0\0" . spl_object_id($this) . '_' . bin2hex(pack('E', microtime(true))) . "\0\0\0";
    }

    /**
     * @return string
     */
    public function getToken(): string
    {
        return $this->token;
    }

    /**
     * @param string $token
     * @param int|null $type
     * @param $emptyFallback
     */
    public function addIn(string $token, ?int $type, $emptyFallback): void
    {
        $this->in[] = [$token, $type, $emptyFallback];
    }

    /**
     * @param string $token
     * @param int|null $type
     */
    public function addValue(string $token, ?int $type): void
    {
        $this->values[] = [$token, $type];
    }

    /**
     * @return array
     */
    public function getIn(): array
    {
        return $this->in;
    }

    /**
     * @return array
     */
    public function getValues(): array
    {
        return $this->values;
    }

}