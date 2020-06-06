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


class Statement
{
    /**
     * @var string
     */
    private $statement;

    /**
     * @var Parameter|Parameter[]
     */
    private $parameters;

    /**
     * Statement constructor.
     *
     * @param string $statement
     * @param Parameter[] $parameters
     */
    public function __construct(string $statement, array $parameters = [])
    {
        foreach ($parameters as $parameter) {
            if (!$parameter instanceof Parameter) {
                throw new \InvalidArgumentException('Parameters must be provided as array of ' . Parameter::class);
            }
        }
        $this->statement = $statement;
        $this->parameters = $parameters;
    }

    /**
     * @return string
     */
    public function getStatement(): string
    {
        return $this->statement;
    }

    /**
     * @return Parameter|Parameter[]
     */
    public function getParameters()
    {
        return $this->parameters;
    }

    /**
     * @param \PDO $pdo
     *
     * @return \PDOStatement|null
     * @throws \Exception
     */
    public function prepare(\PDO $pdo): \PDOStatement
    {
        $statement = $pdo->prepare($this->statement);
        if (!$statement) {
            throw new \Exception('Statement could not be prepared');
        }
        foreach ($this->parameters as $parameter) {
            if (!$statement->bindValue($parameter->getName(), $parameter->getValue(), $parameter->getType())) {
                throw new \Exception(
                    'Value could not be bound'
                );
            }
        }

        return $statement;
    }
}