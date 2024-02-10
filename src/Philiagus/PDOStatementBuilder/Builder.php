<?php
/*
 * This file is part of philiagus/pdostatementbuilder
 *
 * (c) Andreas Eicher <philiagus@philiagus.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Philiagus\PDOStatementBuilder;

use Closure;
use Philiagus\PDOStatementBuilder\Token\AbstractToken;
use Philiagus\PDOStatementBuilder\Token\ForeachToken;
use Philiagus\PDOStatementBuilder\Token\IfToken;
use Philiagus\PDOStatementBuilder\Token\InToken;
use Philiagus\PDOStatementBuilder\Token\RawToken;
use Philiagus\PDOStatementBuilder\Token\Value\ForeachInfoValue;
use Philiagus\PDOStatementBuilder\Token\ValueToken;

class Builder
{
    public const TOKEN_REGEX = '\0\0\0\d+_[0-9a-f]+_\d+_[0-9a-z]+\0\0\0';

    private ?string $unique = null;
    private int $uniqueIndex = 0;
    /** @var AbstractToken[] */
    private array $tokens = [];
    private ?\SplStack $tokenStack = null;

    /**
     * Make sure that no child class will ever take any arguments for construction
     */
    final public function __construct()
    {
    }

    /**
     * A simple way of constructing a statement that doesn't need specific tokens
     * The statement should contain the statement as it would be provided to the prepare
     * method of a \PDO object. The parameters are an array with the key being the name of the parameter in
     * the query.
     *
     * An example statement would be: "SELECT * FROM `table` WHERE id = :id" with the parameters
     * [":id" => 123]
     *
     *
     * @param string $statement
     * @param array $parameters
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
    protected static function transformValue(mixed $value, ?int &$type): mixed
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
        return match (gettype($value)) {
            'string', 'double' => \PDO::PARAM_STR,
            'integer' => \PDO::PARAM_INT,
            'NULL' => \PDO::PARAM_NULL,
            'boolean' => \PDO::PARAM_BOOL,
            default => throw new \InvalidArgumentException('Type of provided ' . gettype($value) . ' argument could not be inferred'),
        };
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

            $interaction = new EvaluationControl(
            // goto
                static function ($target) use (&$currentToken) {
                    $currentToken = $target;
                },
                // continue
                static function () use (
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
                static function (string $raw) use (&$generatedStatement) {
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

    /**
     * @param $value
     * @param int|null $type
     * @param array $generatedParameters
     *
     * @return string
     */
    private function executeValue($value, ?int $type, array &$generatedParameters): string
    {

        if ($this->unique === null) {
            $this->unique = spl_object_id($this) . '_' . bin2hex(pack('E', microtime(true)));
        }

        $name = ':p' . $this->unique . '_' . $this->uniqueIndex++ . 'p';

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
     * Will return a statement to be used for an empty in.
     *
     * The $emptyFallback is the value provided as parameter of the same name when
     * calling the in method
     *
     * @param mixed $emptyFallback
     *
     * @return Statement
     * @see static::in()
     */
    protected static function buildEmptyIn(mixed $emptyFallback): Statement
    {
        if (is_int($emptyFallback)) {
            $emptyFallback = '0' . str_repeat(',0', $emptyFallback - 1);
        }

        if (is_string($emptyFallback)) {
            return new Statement("SELECT $emptyFallback FROM (SELECT 0 FROM dual) `noResultSubSelect` WHERE 0");
        }

        throw new \InvalidArgumentException('Empty fallback for in statement is not valid');
    }

    /**
     * Adds the provided value as a bound parameter to the generated statement.
     *
     * If the provided $value is an object implementing the PDOStatementBuilderParameter interface
     * that method is used to convert the value before binding it
     * as a parameter.
     *
     * $closure expects a \Closure with the signature function(mixed $value, ?int &$type = null): mixed
     * If a $closure is provided, the $value and the $type (after PDOStatementBuilderParameter conversion)
     * are provided to that $closure
     * The return of the function will be used as $value. $type can be altered by reference
     *
     * If $type is null after all of that, the $type is inferred using the method detectType(mixed $value): int
     *
     * @param mixed $value
     * @param int|null $type
     * @param Closure|null $closure
     *
     * @return string
     * @see PDOStatementBuilderParameter
     */
    final public function value(mixed $value, ?int $type = null, ?\Closure $closure = null): string
    {
        $token = new ValueToken($value, $type, $closure);
        $this->tokens[$token->getId()] = $token;

        return $token->getId();
    }

    /**
     * Injects the value as string into the resulting statement string, without escaping or binding the value in
     * any form. This can be used for dynamic parts of a statement.
     *
     * @param mixed $value
     * Any value, but preferably something that can be converted to a string (possibly after using $closure)
     * as it will be string-concat into the resulting statement
     *
     * @param Closure|null $closure
     * The signature of $closure should be function(mixed $value): string
     * If given, $closure is provided with the provided $value and the return value of the $closure is used
     * in the statement string
     *
     * @return string
     */
    final public function raw(mixed $value, ?\Closure $closure = null): string
    {
        $token = new RawToken($value, $closure);
        $this->tokens[$token->getId()] = $token;

        return $token->getId();
    }

    /**
     * Builds an in statement part from the provided data. For a typical SQL statement, this would be written
     * between the brackets, such as `column in ({$builder->in($array)})`
     *
     * Arrays of any depth are supported, so that result statement fragments such as "(1,2,3),(2,3,4)" are possible
     * For an array resulting in that type of binding, $emptyFallback should be configured to 3 (as one element of
     * the result contains 3 sub-elements).
     * If a more complex variant of empty fallback is needed, please extend the builder and overwrite the buildEmptyIn
     * method to your needs
     *
     * The individual values to be bound will be provided to the value method, providing the given type
     *
     * @param mixed $data
     * Can be an array of data, a BuilderValue providing an array of data or a Statement-object build by a Builder
     * to be inserted at the target location
     *
     * @param int|null $type
     * @param mixed|Statement $emptyFallback
     *
     * @return string
     * @see static::buildEmptyIn()
     * @see static::value()
     */
    final public function in(mixed $data, ?int $type = null, mixed $emptyFallback = 1): string
    {
        $token = new InToken($data, $type, $emptyFallback);
        $this->tokens[$token->getId()] = $token;

        return $token->getId();
    }

    /**
     * Starts an if block. All the content of the if block up to the next corresponding elseif, else or endif
     * are only used in the statement, if the $truthy value is true.
     *
     * This behaves exactly like a php if-structure behaves.
     *
     * $closure should have the signature function(mixed $truthy): mixed and can be used to alter the $truthy value
     * before evaluation. This is needed when the value provided is a BuilderValue, such as written by reference
     * when using foreach($source, $value)
     *
     * @param mixed $truthy
     * @param Closure|null $closure
     *
     * @return string
     * @see PDOStatementBuilderParameter
     */
    final public function if(mixed $truthy, ?\Closure $closure = null): string
    {
        $token = new IfToken($truthy, $closure);
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
     * The parameters behave exactly like the if
     *
     * @param mixed $truthy
     * @param Closure|null $closure
     *
     * @return string
     * @see self::if()
     */
    final public function elseif(mixed $truthy, ?\Closure $closure = null): string
    {
        $token = $this->getCurrentToken(
            IfToken::class,
            'Trying to create elseif outside if structure'
        );

        $id = $token->elseif($truthy, $closure);
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
     * Defines the and of an if, elseif or else, closing the entire if structure it belongs to
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
     * Uses the value as iterable and loops through the data
     *
     * Pretend that foreach($array, $value, $key) is identical to foreach($array as $key => $value)
     *
     * $value, $key and $info will each be set by reference to an object that is later loaded with the information when
     * looping through the values of $source.
     *
     *
     * @param iterable|BuilderValue $source
     * The value to iterate over. If this value is not an array its values will be extracted and internally stored
     * as an array before starting the loop.
     *
     * @param mixed $value
     * Variable will be overwritten on time of call with a WritableValue object that will transport the current value
     * of the foreach to the desired location in the statement.
     * The same variable can later be used in the same statement for another foreach or in any context, but please be
     * aware that this will behave exactly like using the same variable name in to normal foreach calls in PHP
     *
     * @param mixed $key
     * Behaves just as the $value, but is given the key of the looped
     *
     * @param mixed $info
     * Will be changed to an object of type `ForeachInfoValue` that provides
     * `$info->first` - can be used with if($info->first) to identify the first loop iteration
     * `$info->last` - can be used with if($info->last) to identify the last loop iteration
     * `$info->index` - provides the index of the current iteration, starting to count at 0
     * `$info->count` - provides the total number of elements the foreach will loop over
     * The object provided by $info cannot be used as a statement value itself. You cannot use it in
     * foreach, if, elseif, raw or value. Only its fields are available
     *
     * @param Closure|null $closure
     * Should have the signature function(mixed $source): iterable
     * If given: Will be provided the source and its return value will be used for the foreach
     * Can be used when providing a carry value from another builder function (such as another foreach)
     * and wanting to alter that value before iteration
     *
     * @return string
     * @see ForeachInfoValue
     */
    final public function foreach(
        mixed     $source,
        mixed     &$value,
        mixed     &$key = null,
        mixed     &$info = null,
        ?\Closure $closure = null
    ): string
    {
        $token = new ForeachToken($source, $value, $key, $info, $closure);
        $this->tokens[$token->getId()] = $token;
        $this->stackPush($token);

        return $token->getId();
    }

    /**
     * Pops an element from the token stack
     */
    private function stackPop(): void
    {
        $this->getTokenStack()->pop();
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
