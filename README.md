# philiagus/pdostatementbuilder
PHP classes to easily build PDO statements.

## Why?

It is very common in database related coding to build statements based on a set of input parameters provided to a repository. This usually looks something like this:

```php
class SomeRepository
{
    /**
     * @type \PDO
     */
    private $pdo;

    function getSomething(array $ids, bool $someFilter = false, ?int $exlude = null): array
    {
        if (empty($ids)) {
            return [];
        }
        $statement = "SELECT *
                FROM `table`
                WHERE id IN (?" . str_repeat(',?', count($ids) - 1) . ")";
        $params = array_values($ids);

        if ($someFilter) {
            $statement .= " AND `filter_field` IS NOT NULL AND `filter_field2` = 1";
        }

        if ($exlude !== null) {
            $statement .= ' AND `explude_column` = ?';
            $params[] = $exlude;
        }

        $statement = $this->pdo->prepare($statement);
        $statement->execute($params);

        return $statement->fetchAll(\PDO::FETCH_ASSOC);
    }
}
```

Compare this to the following code:

```php
class SomeRepository
{
    /**
     * @type \PDO
     */
    private $pdo;

    function getSomething(array $ids, bool $someFilter = false, ?int $exclude = null): array
    {
        if (empty($ids)) {
            return [];
        }

        $builder = new \Philiagus\PDOStatementBuilder\PDOStatementBuilder();
        $sql = $builder->prepare("
            SELECT *
            FROM table
            WHERE id IN ({$builder->in($ids)})
            /* {$builder->if($someFilter)} */
                AND `filter_field` IS NOT NULL AND `filter_field2` = 1
            /* {builder->endif()} */
            /* {builder->if($exclude !== null)} */
                AND `explude_column` = {$builder->value($exclude)}
            /* {$builder->endif()} */
            "
        );

        $statement = $sql->prepare($this->pdo);
        $statement->execute();
        return $statement->fetchAll(\PDO::FETCH_ASSOC);
    }
}
```

The code does essentially the same, but the SQL is readable. Additionally, an IDE such as PHPStorm can help you with auto-completion of the SQL.