# UPDATE
## v1.0.0 - v1.0.1

**This is a bugfix release.** The default empty in previously generated statements selecting from `` `dual` ``, which should just be `dual`, for MySQL to understand it correctly.

## v1.0.0-RC8 - v1.0.0

Previously it was possible for Statements, built via `::simple`, to overwrite each others parameters in a scenario as follows:

```php
$statement1 = \Philiagus\PDOStatementBuilder\Builder::simple('SELECT :var', [':var' => 1]);
$statement2 = \Philiagus\PDOStatementBuilder\Builder::simple('SELECT :var', [':var' => 2]);
$builder = new \Philiagus\PDOStatementBuilder\Builder();
$builder->build("
    SELECT *
    FROM `table`
    WHERE 
        `column1` in ({$builder->in($statement1)}) AND
        `column2` in ({$builder->in($statement2)})
");
```

Previously, the second `in` would have overwritten `:var` to be 2, as it was defined in both statements. From now on a `\LogicException` is thrown instead.

This is to prevent wrong and potentially harmful statements from being built and then executed.