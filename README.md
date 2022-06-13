# Json to PHP classes
Easy converts json to php classes, parse json array to array of classes. Good tool to create bridge with excited API based on json format.

## Install via composer
```shell
composer require --dev uni-method/json-to-php-class
```

### By example
Current json
```json
{
  "count": 150,
  "same": [
    {
      "length": 22.34,
      "tag": {
        "name": "zip"
      }
    },
    {
      "length": 160.84
    }
  ]
}
```

will be converted into three php files

```php
<?php

namespace App\Model;

class Root
{
    protected int $count;
    /**
     * @var Same[]
     */
    protected array $same;
    public function getCount() : int
    {
        return $this->count;
    }
    public function setCount(int $count) : void
    {
        $this->count = $count;
    }
    /**
     * @return Same[]
     */
    public function getSame() : array
    {
        return $this->same;
    }
    /**
     * @param Same[] $same
     */
    public function setSame(array $same) : void
    {
        $this->same = $same;
    }
}
```

```php
<?php

namespace App\Model;

class Same
{
    protected float $length;
    protected Tag $tag;
    public function getLength() : float
    {
        return $this->length;
    }
    public function setLength(float $length) : void
    {
        $this->length = $length;
    }
    public function getTag() : Tag
    {
        return $this->tag;
    }
    public function setTag(Tag $tag) : void
    {
        $this->tag = $tag;
    }
}
```

```php
<?php

namespace App\Model;

class Tag
{
    protected string $name;
    public function getName() : string
    {
        return $this->name;
    }
    public function setName(string $name) : void
    {
        $this->name = $name;
    }
}
```

## How to use
Create `script.php`
and copy code
```php
<?php declare(strict_types=1);

use PhpParser\PrettyPrinter;
use UniMethod\JsonToPhpClass\AstBuilder;
use UniMethod\JsonToPhpClass\Converter;

require_once __DIR__ . '/vendor/autoload.php';

$json = file_get_contents($argv[1]);
$path = $argv[2] ?? __DIR__;
$namespace = $argv[3] ?? 'App\\Model';

$converter = new Converter();
$prettyPrinter = new PrettyPrinter\Standard();
$ast = new AstBuilder($namespace);
$classes = $converter->convert($json);

foreach ($classes as $class) {
    $fullPath = $path . '/' . $class->getName() . '.php';
    $body = $ast->build($class);
    file_put_contents($fullPath, $prettyPrinter->prettyPrintFile([$body]));
}
```

run local path
```shell
php script.php /some/local/path/input.json
```

specify destination path
```shell
php script.php /some/local/path/input.json /put/generated/files/here
```

specify namespace
```shell
php script.php /some/local/path/input.json /put/generated/files/here "App\Dto"
```

enjoy new classes

## Development

### Run tests
```shell
vendor/bin/phpunit
```

### Run static analyser
```shell
vendor/bin/phpstan analyse src tests
```
