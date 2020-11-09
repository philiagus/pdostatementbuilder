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

use Philiagus\PDOStatementBuilder\Token\AbstractToken;
use Philiagus\PDOStatementBuilder\Token\ForeachToken;
use Philiagus\PDOStatementBuilder\Token\IfToken;
use Philiagus\PDOStatementBuilder\Token\InToken;
use Philiagus\PDOStatementBuilder\Token\RawToken;
use Philiagus\PDOStatementBuilder\Token\ValueToken;

class Builder
{
    public const TOKEN_REGEX = '\0\0\0\d+_[0-9a-f]+_\d+_[0-9a-z]+\0\0\0';

    private const PARAMETER_CONSTRUCT = ":pUp";

    /**
     * @var string|null
     */
    private $unique = null;

    /**
     * @var int
     */
    private $uniqueIndex = 0;

    /**
     * @var AbstractToken[]
     */
    private $tokens = [];

    /**
     * @var \SplStack|null
     */
    private $tokenStack = null;

    /**
     * Make sure that no child class will ever take any arguments for construction
     */
    final public function __construct()
    {
    }

    /**
     * A simple way of constructing a statement that doesn't needs specific tokens
     * The statement should contain the statement as it would be provided to the prepare
     * method of a \PDO object. The parameters are an array with the key being the name of the parameter in
     * the query.
     *
     * An example statement would be: "SELECT * FROM `table` WHERE id = :id" with the parameters
     * [":id" => 123]
     *
     * @param string $statement
     * @param mixed[] $parameters
     *
     * @return Statement
     */
    public static function simple(string $statement, array $parameters = []): Statement
    {
        if (empty($parameters)) {
            return new Statement($statement);
        }

        $statementParameters = [];
        foreach ($parameters as $name => $value) {
            $type = null;
            $value = static::transformValue($value, $type);

            if ($type === null) {
                $type = static::detectType($value);
            } elseif (!is_int($type)) {
                throw new \LogicException(
                    'transformValue transformed type to be neither null nor integer'
                );
            }

            $statementParameters[] = new Parameter(
                $name,
                $value,
                $type
            );
        }

        return new Statement($statement, $statementParameters);
    }

    /**
     * Used to transform a value to the needed format used in the statement
     * By default this method identifies PDOStatementBuilderParameter objects and
     * uses the corresponding method.
     *
     * @param mixed $value
     * @param int|null $type
     *
     * @return mixed
     */
    protected static function transformValue($value, ?int &$type)
    {
        if ($value instanceof PDOStatementBuilderParameter) {
            return $value->toPDOStatementValue($type);
        }

        return $value;
    }

    /**
     * Used to infer the type of the provided argument. Should return one of the \PDO::PARAM_* constants
     * or throw an \InvalidArgumentException if no type could be inferred
     *
     * @param $value
     *
     * @return int
     * @throws \InvalidArgumentException
     */
    protected static function detectType($value): int
    {
        switch (gettype($value)) {
            case 'string':
            case 'double':
                return \PDO::PARAM_STR;
            case 'integer':
                return \PDO::PARAM_INT;
            case 'NULL':
                return \PDO::PARAM_NULL;
            case 'boolean':
                return \PDO::PARAM_BOOL;
            default:
                throw new \InvalidArgumentException('Type of provided ' . gettype($value) . ' argument could not be inferred');
        }
    }

    /**
     * Used to build a statement by parsing the string and tokens created with the other functions
     * of the builder.
     *
     * @param string $statement
     *
     * @return Statement
     */
    final public function build(string $statement): Statement
    {
        try {
            // check for tokens
            preg_match_all('~' . self::TOKEN_REGEX . '~s', $statement, $matches);
            if (array_keys($this->tokens) !== $matches[0]) {
                throw new \LogicException('The tokens in the statement do not match the expected tokens. Did you temper with the generated string?');
            }

            if (empty($this->tokens)) {
                return new Statement($statement, []);
            }

            foreach ($this->tokens as $token) {
                $token->assertClosed();
            }
            /** @var null|string $currentToken */
            $currentToken = null;
            $generatedStatement = '';
            $generatedParameters = [];

            $tokens = array_keys($this->tokens);
            $rawParts = preg_split('~' . self::TOKEN_REGEX . '~', $statement);
            $generatedStatement .= array_shift($rawParts);
            $tokenToFollowingRaw = array_combine(array_keys($this->tokens), $rawParts);
            $tokenToNextToken = array_combine(
                $tokens,
                array_merge(array_slice($tokens, 1), [null])
            );

            $interaction = null;
            $interaction = new EvaluationControl(
            // goto
                function ($target) use (&$currentToken) {
                    $currentToken = $target;
                },
                // continue
                function () use (
                    $statement,
                    &$generatedStatement,
                    &$currentToken,
                    $tokenToFollowingRaw,
                    $tokenToNextToken
                ) {
                    $generatedStatement .= $tokenToFollowingRaw[$currentToken];
                    $currentToken = $tokenToNextToken[$currentToken];
                },
                // value
                function ($value, ?int $type) use (&$generatedStatement, &$generatedParameters) {
                    $generatedStatement .= $this->executeValue($value, $type, $generatedParameters);
                },
                // in
                function ($data, ?int $type, $emptyFallback) use (&$generatedStatement, &$generatedParameters) {
                    $generatedStatement .= $this->executeIn($data, $type, $emptyFallback, $generatedParameters);
                },
                //raw
                function (string $raw) use (&$generatedStatement) {
                    $generatedStatement .= $raw;
                }
            );

            $currentToken = $tokens[0];
            while ($currentToken !== null) {
                $this->tokens[$currentToken]->execute($currentToken, $interaction);
            }

            return new Statement($generatedStatement, $generatedParameters);
        } finally {
            $this->tokenStack = null;
            $this->tokens = [];
        }
    }

    private function executeValue($value, ?int $type, array &$generatedParameters): string
    {
        $name = str_replace('U', $this->getUnique(), self::PARAMETER_CONSTRUCT);

        $value = static::transformValue($value, $type);

        if ($type === null) {
            $type = static::detectType($value);
        } elseif (!is_int($type)) {
            throw new \LogicException(
                'transformValue transformed type to be neither null nor integer'
            );
        }

        $generatedParameters[$name] = new Parameter($name, $value, $type);

        return $name;
    }

    /**
     * Returns a unique identifier
     *
     * @return string
     */
    private function getUnique(): string
    {
        if ($this->unique === null) {
            $this->unique = spl_object_id($this) . '_' . bin2hex(pack('E', microtime(true)));
        }

        return $this->unique . '_' . $this->uniqueIndex++;
    }

    /**
     * @param $data
     * @param int|null $type
     * @param $emptyFallback
     * @param array $generatedParameters
     *
     * @return string
     */
    private function executeIn($data, ?int $type, $emptyFallback, array &$generatedParameters): string
    {
        if (!is_array($data) && !($data instanceof Statement)) {
            throw new \InvalidArgumentException(
                'in data must be provided as array or instance of Statement'
            );
        }

        if (!($data instanceof Statement)) {
            if (empty($data)) {
                if ($emptyFallback instanceof Statement) {
                    $data = $emptyFallback;
                } else {
                    $data = static::buildEmptyIn($emptyFallback);
                }
            }
        }

        if ($data instanceof Statement) {
            foreach ($data->getParameters() as $parameter) {
                $parameterName = $parameter->getName();
                if (isset($generatedParameters[$parameterName])) {
                    throw new \LogicException("Sub-Statement error: Parameter $parameterName would be bound twice");
                }
                $generatedParameters[$parameterName] = $parameter;
            }

            return $data->getStatement();
        }

        $recursiveBinder = null;
        $recursiveBinder = function ($element) use (&$generatedStatement, &$generatedParameters, &$recursiveBinder, $type): string {
            if (is_array($element)) {
                return '(' . implode(', ', array_map(
                        $recursiveBinder, $element
                    )) . ')';
            }

            return $this->executeValue($element, $type, $generatedParameters);
        };

        return implode(', ', array_map(
            $recursiveBinder,
            $data
        ));
    }

    /**
     * @param $emptyFallback
     *
     * @return Statement
     */
    protected static function buildEmptyIn($emptyFallback): Statement
    {
        if (is_int($emptyFallback)) {
            $emptyFallback = '0' . str_repeat(',0', $emptyFallback - 1) . '';
        }

        if (is_string($emptyFallback)) {
            return new Statement("SELECT $emptyFallback FROM (SELECT 0 FROM dual) `noResultSubSelect` WHERE 0");
        }

        throw new \InvalidArgumentException(
            'Empty fallback for in statement is not valid'
        );
    }

    /**
     * @param $value
     * @param int|null $type
     *
     * @return string
     */
    final public function value($value, ?int $type = null): string
    {
        $token = new ValueToken($value, $type);
        $this->tokens[$token->getId()] = $token;

        return $token->getId();
    }

    /**
     * Injects the value as string into the resulting statement string
     * This is most times used in context of foreach values
     *
     * @param $value
     *
     * @return string
     */
    final public function raw($value): string
    {
        $token = new RawToken($value);
        $this->tokens[$token->getId()] = $token;

        return $token->getId();
    }

    /**
     * @param $data
     * @param int|null $type
     * @param mixed|Statement $emptyFallback
     *
     * @return string
     */
    final public function in($data, ?int $type = null, $emptyFallback = 1): string
    {
        $token = new InToken($data, $type, $emptyFallback);
        $this->tokens[$token->getId()] = $token;

        return $token->getId();
    }

    /**
     * Starts an if block. Conversion is provided with the $truthy value, which is needed
     * when using conditional if in context of foreach keys and values
     *
     * @param $truthy
     * @param callable|null $conversion
     *
     * @return string
     */
    final public function if($truthy, ?callable $conversion = null): string
    {
        $token = new IfToken($truthy, $conversion);
        $this->tokens[$token->getId()] = $token;
        $this->stackPush($token);

        return $token->getId();
    }

    /**
     * Pushes an element onto the stack
     *
     * @param AbstractToken $token
     */
    private function stackPush(AbstractToken $token): void
    {
        $this->getTokenStack()->push($token);
    }

    /**
     * @return \SplStack
     */
    private function getTokenStack(): \SplStack
    {
        if ($this->tokenStack === null) {
            $this->tokenStack = new \SplStack();
        }

        return $this->tokenStack;
    }

    /**
     * Branches from an if into an elseif
     *
     * @param $truthy
     *
     * @param callable|null $conversion
     *
     * @return string
     */
    final public function elseif($truthy, ?callable $conversion = null): string
    {
        $token = $this->getCurrentToken(
            IfToken::class,
            'Trying to create elseif outside if structure'
        );

        $id = $token->elseif($truthy, $conversion);
        $this->tokens[$id] = $token;

        return $id;
    }

    /**
     * Returns the current token and optionally validates it
     * to be the expected type
     *
     * @param string|null $expectedType
     * @param string|null $exceptionMessage
     *
     * @return AbstractToken
     */
    private function getCurrentToken(
        string $expectedType = null,
        string $exceptionMessage = null
    ): AbstractToken
    {
        $stack = $this->getTokenStack();
        if ($stack->isEmpty()) {
            throw new \LogicException($exceptionMessage);
        }

        $top = $stack->top();
        if (!($top instanceof $expectedType) || !($top instanceof AbstractToken)) {
            throw new \LogicException($exceptionMessage);
        }

        return $top;
    }

    /**
     * Branches from an if or elseif into an else
     *
     * @return string
     */
    final public function else(): string
    {
        $token = $this->getCurrentToken(
            IfToken::class,
            'Trying to create else outside if structure'
        );

        $id = $token->else();
        $this->tokens[$id] = $token;

        return $id;
    }

    /**
     * Defines the and of an if, elseif or else, closing the entire if structure
     * it belongs to
     *
     * @return string
     */
    final public function endif(): string
    {
        $token = $this->getCurrentToken(
            IfToken::class,
            'Trying to create endif outside if structure'
        );

        $id = $token->endif();
        $this->tokens[$id] = $token;
        $this->stackPop();

        return $id;
    }

    /**
     * Pops an element from the token stack
     */
    private function stackPop(): void
    {
        $this->getTokenStack()->pop();
    }

    /**
     * Uses the value as iterable and loops through the data
     * $keyIdentifier and $valueIdentifier will be loaded with tokens that can be used in() and value()
     *
     * @param $value
     * @param $keyIdentifier
     * @param $valueIdentifier
     *
     * @return string
     */
    final public function foreach($value, &$valueIdentifier, &$keyIdentifier = null): string
    {
        $token = new ForeachToken($value, $valueIdentifier, $keyIdentifier);
        $this->tokens[$token->getId()] = $token;
        $this->stackPush($token);

        return $token->getId();
    }

    /**
     * Ends a foreach bracket
     *
     * @return string
     */
    final public function endforeach(): string
    {
        $foreach = $this->getCurrentToken(
            ForeachToken::class,
            'Trying to create endforeach outside foreach'
        );

        $id = $foreach->end();
        $this->tokens[$id] = $foreach;
        $this->stackPop();

        return $id;
    }

}