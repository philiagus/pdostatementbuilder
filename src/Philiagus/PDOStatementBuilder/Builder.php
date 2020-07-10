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

class Builder
{

    private const CONSTRUCT_ROOT = 'root';
    private const CONSTRUCT_IF = 'if';
    private const CONSTRUCT_FOREACH = 'foreach';


    private const UNIQUE_REGEX = '\d+_[0-9a-f]+_\d+';
    public const PARAMETER_REGEX = '/:p' . self::UNIQUE_REGEX . 'p/';
    public const TOKEN_REGEX = '/\0\0\0' . self::UNIQUE_REGEX . '\0\0\0/';

    private const TOKEN_CONSTRUCT = "\0\0\0U\0\0\0";
    private const PARAMETER_CONSTRUCT = ":pUp";

    /**
     * @var bool
     */
    private $used = false;

    /**
     * @var string|null
     */
    private $unique = null;

    /**
     * @var int
     */
    private $uniqueIndex = 0;

    /**
     * @var mixed[]
     */
    private $parameters = [];

    /**
     * @var array
     */
    private $tokens = [];

    /**
     * @var string[]
     */
    private $tokenOrder = [];

    /**
     * @var \SplStack|null
     */
    private $tokenStack = null;

    /**
     * @var array<string,ReplacementMarker>
     */
    private $availableReplacementMarkers = [];

    /**
     * @var array<int,string[]>
     */
    private $tokenByDepth = [];

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
        if ($this->used) {
            throw new \LogicException(
                'Build cannot be called twice on the same builder'
            );
        }
        $this->used = true;

        // extract parameters from query
        $foundParameters = [];
        if (preg_match_all(self::PARAMETER_REGEX, $statement, $matches)) {
            foreach ($matches[0] as $match) {
                if (!isset($this->parameters[$match])) {
                    throw new \LogicException(
                        "An unknown parameter $match was found in the statement. Did you call prepare on a builder not used to bind the parameters?"
                    );
                }
                $foundParameters[$match] = true;
            }
        }
        if (!empty(array_diff_key($this->parameters, $foundParameters))) {
            throw new \LogicException(
                "Not all parameters bound by this builder have been used in the statement. Did you remove parts of the generated string?"
            );
        }
        unset($foundParameters);

        // parse tokens of query
        $tokens = [];
        if (preg_match_all(self::TOKEN_REGEX, $statement, $matches)) {
            $tokens = $matches[0];
        }
        if ($tokens !== $this->tokenOrder) {
            throw new \LogicException(
                "The tokens in the statement do not match the expected tokens. Did you temper with the generated string?"
            );
        }


        krsort($this->tokenByDepth);
        // evaluate tokens
        foreach ($this->tokenByDepth as $tokens) {
            foreach ($tokens as $tokenId) {
                if(!isset($this->tokens[$tokenId])) {
                    continue;
                }
                $token = $this->tokens[$tokenId];
                switch ($token->type) {
                    case self::CONSTRUCT_IF:
                        if (!$token->closed) {
                            throw new \LogicException('Unclosed if detected');
                        }

                        $activeStart = array_search(true, $token->truth);
                        $replaceWith = '';
                        if ($activeStart !== false) {
                            preg_match('/' .
                                preg_quote($token->tokens[$activeStart]) . '(.*?)' .
                                preg_quote($token->tokens[$activeStart + 1]) . '/s',
                                $statement, $matches
                            );
                            $replaceWith = $matches[1];
                        }
                        $statement = preg_replace('/' . preg_quote($token->tokens[0]) . '.*?' . preg_quote(end($token->tokens)) . '/s', $replaceWith, $statement);
                        break;
                    case self::CONSTRUCT_FOREACH:
                        if (!$token->closed) {
                            throw new \LogicException('Unclosed foreach detected');
                        }
                        preg_match('/' . preg_quote($token->tokens[0]) . '(?<content>.*?)' . preg_quote($token->tokens[1]) . '/s', $statement, $matches);
                        $content = $matches['content'];
                        $loopContent = '';
                        foreach ($token->array as $key => $value) {
                            $strtr = [];
                            /** @var ReplacementMarker $replacer */
                            foreach (
                                [
                                    [$token->keyId, $key],
                                    [$token->valueId, $value],
                                ]
                                as [$replacer, $data]
                            ) {
                                foreach ($replacer->getIn() as [$replacementToken, $type, $emptyFallback]) {
                                    $strtr[$replacementToken] = $this->in($data, $type, $emptyFallback);
                                }
                                foreach ($replacer->getValues() as [$replacementToken, $type]) {
                                    $strtr[$replacementToken] = $this->value($data, $type);
                                }
                            }
                            $loopContent .= strtr($content, $strtr);
                        }
                        $statement = str_replace($matches[0], $loopContent, $statement);
                        break;
                }
            }
        }

        // only bind leftover parameters
        if (preg_match_all(self::PARAMETER_REGEX, $statement, $matches)) {
            $parameters = array_values(array_intersect_key($this->parameters, array_flip($matches[0])));
        } else {
            $parameters = [];
        }

        $this->parameters = [];
        $this->tokens = [];
        $this->tokenByDepth = [];
        $this->tokenStack = null;

        return new Statement($statement, $parameters);
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
        if (!$this->accepts()) {
            return '[IGNORED]';
        }

        if ($data instanceof ReplacementMarker) {
            if (!isset($this->availableReplacementMarkers[$data->getToken()])) {
                throw new \LogicException(
                    'Using a replacement marker outside of the corresponding structure'
                );
            }
            $token = $this->generateLogicToken();
            $data->addIn($token, $type, $emptyFallback);

            return $token;
        }

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
                $this->parameters[$parameter->getName()] = $parameter;
            }

            return $data->getStatement();
        }

        $recursiveBinder = null;
        $recursiveBinder = function ($element) use (&$recursiveBinder, $type): string {
            if (is_array($element)) {
                return '(' . implode(', ', array_map(
                        $recursiveBinder, $element
                    )) . ')';
            }

            return $this->value($element, $type);
        };

        return implode(', ', array_map(
            $recursiveBinder,
            $data
        ));
    }

    /**
     * @return bool
     */
    private function accepts(): bool
    {
        return $this->getCurrentToken()->accepts;
    }

    /**
     * Returns the current token and optionally validates it
     * to be the expected type
     *
     * @param string|null $expectedType
     * @param string|null $exceptionMessage
     *
     * @return object
     */
    private function getCurrentToken(
        ?string $expectedType = null,
        ?string $exceptionMessage = null
    ): object
    {
        $stack = $this->getTokenStack();
        if ($stack->isEmpty()) {
            $token = (object) [
                'type' => self::CONSTRUCT_ROOT,
                'accepts' => true,
            ];
        } else {
            $token = $this->tokens[$stack->top()] ?? (object) [
                    'type' => self::CONSTRUCT_ROOT,
                ];
        }

        if ($expectedType === null) {
            return $token;
        }

        if ($token->type !== $expectedType) {
            throw new \LogicException($exceptionMessage);
        }

        return $token;
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
     * Generates a logic token
     *
     * @return string
     */
    private function generateLogicToken(): string
    {
        $token = str_replace(
            'U',
            $this->getUnique(),
            self::TOKEN_CONSTRUCT
        );
        $this->tokenOrder[] = $token;
        $this->tokenByDepth[$this->getTokenStack()->count()][] = $token;

        return $token;
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
     * @param $emptyFallback
     *
     * @return Statement
     */
    protected static function buildEmptyIn($emptyFallback): Statement
    {
        if (is_int($emptyFallback)) {
            $emptyFallback = '(0' . str_repeat(',0', $emptyFallback - 1) . ')';
        }

        if (is_string($emptyFallback)) {
            return new Statement("SELECT $emptyFallback FROM (SELECT 0 FROM `dual`) `noResultSubSelect` WHERE 0");
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
        if (!$this->accepts()) {
            return '[IGNORED]';
        }

        if ($value instanceof ReplacementMarker) {
            if (!isset($this->availableReplacementMarkers[$value->getToken()])) {
                throw new \LogicException(
                    'Using a replacement marker outside of the corresponding structure'
                );
            }
            $token = $this->generateLogicToken();
            $value->addValue($token, $type);

            return $token;
        }

        $name = str_replace('U', $this->getUnique(), self::PARAMETER_CONSTRUCT);

        $value = static::transformValue($value, $type);

        if ($type === null) {
            $type = static::detectType($value);
        } elseif (!is_int($type)) {
            throw new \LogicException(
                'transformValue transformed type to be neither null nor integer'
            );
        }

        $parameter = new Parameter($name, $value, $type);

        $this->parameters[$name] = $parameter;

        return $name;
    }

    /**
     * @param $truthy
     *
     * @return string
     */
    final public function if($truthy): string
    {
        if ($truthy instanceof ReplacementMarker) {
            throw new \LogicException('Loop element cannot be used in logic constructs');
        }

        $token = $this->generateLogicToken();

        $this->tokens[$token] = (object) [
            'token' => $token,
            'accepts' => (bool) $truthy,
            'else' => false,
            'closed' => false,
            'type' => self::CONSTRUCT_IF,
            'tokens' => [$token],
            'truth' => [(bool) $truthy],
        ];

        $this->stackPush($token);

        return $token;
    }

    /**
     * Pushes an element onto the stack
     *
     * @param string $token
     */
    private function stackPush(string $token): void
    {
        $this->getTokenStack()->push($token);
    }

    /**
     * Branches from an if into an elseif
     *
     * @param $truthy
     *
     * @return string
     */
    final public function elseif($truthy): string
    {
        if ($truthy instanceof ReplacementMarker) {
            throw new \LogicException('Loop element cannot be used in logic constructs');
        }

        $if = $this->getCurrentToken(
            self::CONSTRUCT_IF,
            'Trying to create elseif outside if structure'
        );

        if ($if->else) {
            throw new \LogicException(
                'Trying to create elseif after else'
            );
        }

        $token = $this->generateLogicToken();

        $if->accepts = $truthy && !in_array(true, $if->truth);
        $if->truth[] = (bool) $truthy;
        $if->tokens[] = $token;

        return $token;
    }

    /**
     * Branches from an if or elseif into an else
     *
     * @return string
     */
    final public function else(): string
    {
        $if = $this->getCurrentToken(
            self::CONSTRUCT_IF,
            'Trying to create else outside if structure'
        );

        if ($if->else) {
            throw new \LogicException(
                'Trying to create else after else'
            );
        }

        $token = $this->generateLogicToken();

        $if->accepts = !in_array(true, $if->truth);
        $if->truth[] = $if->accepts;
        $if->tokens[] = $token;
        $if->else = true;

        return $token;
    }

    /**
     * Defines the and of an if, elseif or else, closing the entire if structure
     * it belongs to
     *
     * @return string
     */
    final public function endif(): string
    {
        $if = $this->getCurrentToken(
            self::CONSTRUCT_IF,
            'Trying to create endif outside if structure'
        );

        $token = $this->generateLogicToken();

        $if->tokens[] = $token;
        $if->closed = true;

        $this->stackPop();

        return $token;
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
        if ($value instanceof ReplacementMarker) {
            throw new \LogicException('Loop element cannot be used in logic constructs');
        }

        $token = $this->generateLogicToken();

        if (!is_iterable($value)) {
            throw new \InvalidArgumentException(
                'The argument provided to foreach must be iterable'
            );
        }

        $keyIdentifier = new ReplacementMarker();
        $valueIdentifier = new ReplacementMarker();

        $this->tokens[$token] = (object) [
            'token' => $token,
            'array' => $value,
            'accepts' => true,
            'closed' => false,
            'type' => self::CONSTRUCT_FOREACH,
            'tokens' => [$token],
            'keyId' => $keyIdentifier,
            'valueId' => $valueIdentifier,
        ];

        $this->availableReplacementMarkers[$keyIdentifier->getToken()] = $keyIdentifier;
        $this->availableReplacementMarkers[$valueIdentifier->getToken()] = $valueIdentifier;

        $this->stackPush($token);

        return $token;
    }

    /**
     * Ends a foreach bracket
     *
     * @return string
     */
    final public function endforeach(): string
    {
        $foreach = $this->getCurrentToken(
            self::CONSTRUCT_FOREACH,
            'Trying to create endforeach outside foreach'
        );

        $token = $this->generateLogicToken();
        $foreach->tokens[] = $token;
        $foreach->closed = true;
        $this->stackPop();

        unset($this->availableReplacementMarkers[$foreach->keyId->getToken()]);
        unset($this->availableReplacementMarkers[$foreach->valueId->getToken()]);

        return $token;
    }

}