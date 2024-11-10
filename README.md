# philiagus/pdostatementbuilder
PHP classes to easily build PDO statements.

For all update information please refer to [UPDATE.md](./UPDATE.md).

## What is it?

A simple way of building complex PDOStatements in a readable way. Want to build an overly complex filter SQL for your project? This is the project to use!

## Is it covered?
- 100% Test covered
- Tested in
    - PHP8.0
    - PHP8.1
    - PHP8.2
    - PHP8.3

## Why do all this?

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

        $builder = new \Philiagus\PDOStatementBuilder\Builder();
        $sql = $builder->build("
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

### Simple Statement building

The Builder also supports a static method to simply build Statements.

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

        $builder = new \Philiagus\PDOStatementBuilder\Builder();
        $sql = Builder::simple(
            "SELECT * FROM `table` WHERE `id` = :id",
            [':id' => 1]
        );

        $statement = $sql->prepare($this->pdo);
        $statement->execute();
        return $statement->fetchAll(\PDO::FETCH_ASSOC);
    }
}
```

The `Builder::simple` method does not only take named parameters (`:id`), but also numeric parameters (for binding with `?` in PDO). You can also simply provide an `Parameter` object (`new Parameter(<name>, <value>, <type>)`) if you want in-place control of the parameter type.
