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


    private const UNIQUE_REGEX = '\d+_[0-9a-f]+_\d+';
    public const PARAMETER_REGEX = '/:p' . self::UNIQUE_REGEX . 'p/';
    public const TOKEN_REGEX = '/\0\0\0' . self::UNIQUE_REGEX . ':(?<depth>\d+)\0\0\0/';

    private const TOKEN_CONSTRUCT = "\0\0\0U:D\0\0\0";
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

    final public function build(string $statement): Statement
    {
        if ($this->used) {
            throw new \LogicException(
                'Build cannot be called twice on the same builder'
            );
        }
        $this->used = true;

        // extract parameters from query
        $parameters = [];
        if (preg_match_all(self::PARAMETER_REGEX, $statement, $matches)) {
            foreach (array_unique($matches[0]) as $match) {
                if (!isset($this->parameters[$match])) {
                    throw new \LogicException(
                        "An unknown parameter $match was found in the statement. Did you call prepare on a builder not used to bind the parameters?"
                    );
                }
                $parameters[$match] = $this->parameters[$match];
                unset($this->parameters[$match]);
            }

            if (!empty($this->parameters)) {
                throw new \LogicException(
                    "Not all parameters bound by this builder have been used in the statement. Did you remove parts of the generated string?"
                );
            }
        }

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

        // evaluate tokens
        foreach ($this->tokens as $token) {
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
            }
        }

        // only bind leftover parameters
        if (preg_match_all(self::PARAMETER_REGEX, $statement, $matches)) {
            $parameters = array_values(array_intersect_key($parameters, array_flip($matches[0])));
        } else {
            $parameters = [];
        }

        $this->parameters = [];
        $this->tokens = [];
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
                    $data = $this->buildEmptyIn($emptyFallback);
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
     * @param $emptyFallback
     *
     * @return Statement
     */
    protected function buildEmptyIn($emptyFallback): Statement
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

        $name = str_replace('U', $this->getUnique(), self::PARAMETER_CONSTRUCT);

        $value = $this->transformValue($value, $type);

        if ($type === null) {
            $type = $this->detectType($value);
        } elseif (!is_int($type)) {
            throw new \LogicException(
                'transformValue transformed type to be neither null nor integer'
            );
        }

        $parameter = new Parameter($name, $value, $type);

        $this->parameters[$name] = $parameter;

        return $name;
    }

    private function getUnique(): string
    {
        if ($this->unique === null) {
            $this->unique = spl_object_id($this) . '_' . bin2hex(pack('E', microtime(true)));
        }

        return $this->unique . '_' . $this->uniqueIndex++;
    }

    /**
     * @param mixed $value
     * @param int|null $type
     *
     * @return mixed
     */
    protected function transformValue($value, ?int &$type)
    {
        if ($value instanceof PDOStatementBuilderParameter) {
            return $value->toPDOStatementValue($type);
        }

        return $value;
    }

    /**
     * @param $value
     *
     * @return int
     */
    protected function detectType($value): int
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
     * @param $truthy
     *
     * @return string
     */
    public function if($truthy): string
    {
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
     * @return string
     */
    private function generateLogicToken(): string
    {
        return $this->tokenOrder[] = str_replace(
            ['U', 'D'],
            [$this->getUnique(), $this->getTokenStack()->count()],
            self::TOKEN_CONSTRUCT
        );
    }

    private function stackPush(string $token): void
    {
        $this->getTokenStack()->push($token);
    }

    public function elseif($truthy): string
    {
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

    public function else(): string
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
     * @return string
     */
    public function endif(): string
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
     *
     */
    private function stackPop(): void
    {
        $this->getTokenStack()->pop();
    }

}