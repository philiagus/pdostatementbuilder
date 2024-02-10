# UPDATE

## v1.3.0 - v1.4.0

- Dropped support for PHP8.0
- Tested on PHP8.1, PHP8.2 and PHP8.3
- Reduced size of build artifact
- Updated copyright to name change

## v1.2.0 - v1.3.0

Fixed errors in README.md, still referencing PHP <8.0.

### BC Breaks

- Parameter names have been changed, leading to BC breaks if named parameters are used. This is most likely going to break for uses of `foreach`, so please check your code.
- `$conversion` (no simply named `$closure`) no longer accepts callables and instead expects `\Closure` objects

### Changes to behaviour

- The `$value` provided by `foreach` now also supports `$value->property` resolution

### Changes to methods

- `foreach(mixed $source, mixed &$value, mixed &$key = null, mixed &$info = null, ?\Closure $closure = null): string`:
  - renamed `$value` to `$source`
  - renamed `$valueIdentifier` to `$value`
  - renamed `$keyIdentifier` to `$key`
  - added `$info`
    - will be changed by reference to an object of class `\Philiagus\PDOStatementBuilder\Token\Value\ForeachInfoValue`
    - provides multiple properties to be used in the builders methods
      - `$info->first` - true if this is the first iteration
      - `$info->last` - true if this is the last iteration
      - `$info->count` - count of values to be looped over
      - `$info->index` - current loop index, starting at 0
  - added `$closure`
    - used with signature `function(mixed $source): iterable` to change `$source` before attempting iteration
- `if(mixed $truthy, ?\Closure $closure = null): string`
  - renamed `$conversion` to `$closure`
- `elseif(mixed $truthy, ?\Closure $closure = null): string`
    - renamed `$conversion` to `$closure`
- `raw(mixed $value, ?\Closure $closure = null): string`
  - added `$closure`
    - used with signature `function(mixed $value): string` to change `$value` before adding it to the statement string
- `value(mixed $value, ?int $type = null, ?\Closure $closure = null): string`
  - added `$closure`
    - used with signature `function(mixed $value, ?int &$type = null): string` to change `$value` before binding it as parameter. `$type` can be set by reference and is expected to be a `PDO::PARAM_*` constant.



## v1.1.0 - v1.2.0

**WARNING**: Support for PHP < 8.0 has been dropped

### Fixes

- Fixed inheritance rules for PHP8.1

## v1.0.2 - v1.1.0

### Changes

Renamed the `BuilderValue->get` method to `BuilderValue->resolveAsPDOStatementBuilderValue`. This was done to ensure the
new feature of calling methods on values does not interfere with the naming of the `get` method.

Also renamed the `WritableValue->set` to `WritableValue->setPDOStatementBuilderValue` for the same reason.

### New Features

The carry value objects of the builder can now also act as objects and closures.

```php
class SomeObject {
    public function getSomething($arg): string {
        return $arg . 'baz';
    }
}

$builder = new \Philiagus\PDOStatementBuilder\Builder();
$array = [
    'foo' => new SomeObject(),
    'bar' => new SomeObject()
];
$builder->build("
    SELECT * FROM `tableName` WHERE
    FALSE
    {$builder->foreach($array, $obj, $index)}
        OR `column` = {$builder->value($obj->getSomething($index))}
    {$builder->endforeach()}
");
```

This will on each iteration of the loop call the object, once with the argument `foo` and once with `bar`.

The following method names must - for technical reasons - be implemented differently:

- offsetExists
- offsetGet
- offsetSet
- offsetUnset
- __call
- __invoke
- resolveAsPDOStatementBuilderValue

These methods must be implemented as `new CallValue($valueToCallOn, <name of the method>)`.

You can also invoke values directly as functions.

```php
$builder = new \Philiagus\PDOStatementBuilder\Builder();
$mutators = [
    'strtolower',
    'strtoupper',
];
$builder->build("
    SELECT * FROM `tableName` WHERE
    FALSE
    {$builder->foreach($mutators, $function)}
        OR `column` = {$builder->value($function('stupid EXAMPLE'))}
    {$builder->endforeach()}
");
```

Arguments for both the invocation and the method call are checked to be BuilderValue objects and are correspondingly
resolved.

Long story short: Give it a try!

## v1.0.1 - v1.0.2

**This is a bugfix release.** The default empty expression used brackets around the outermost column definition for
multiple columns (such as `->in([], null, 3)`).

## v1.0.0 - v1.0.1

**This is a bugfix release.** The default empty in previously generated statements selecting from `` `dual` ``, which
should just be `dual`, for MySQL to understand it correctly.

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
