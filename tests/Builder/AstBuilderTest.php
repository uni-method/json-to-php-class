<?php declare(strict_types=1);

namespace UniMethod\JsonToPhpClass\Tests\Builder;

use JsonException;
use PhpParser\PrettyPrinter;
use PHPUnit\Framework\TestCase;
use UniMethod\JsonToPhpClass\Builder\AstBuilder;
use UniMethod\JsonToPhpClass\Converter\Converter;

/**
 * @coversDefaultClass \UniMethod\JsonToPhpClass\Builder\AstBuilder
 */
final class AstBuilderTest extends TestCase
{
    /**
     * @covers ::build
     * @throws JsonException
     */
    public function testBuild(): void
    {
        $json = '{
  "levels": ["new", true],
  "count": 150,
  "props": null,
  "same": [
    {
      "length": 22.34,
      "tag": {
        "local_name": "zip"
      }
    },
    {
      "length": 160
    },
    {
      "length": 160,
      "items": [{"id": "unique"}]
    }
  ]
}';
        $namespace = 'App\\Model';

        $expected = [
            [
                'Tag',
                '<?php

declare (strict_types=1);
namespace App\Model;

class Tag
{
    protected ?string $localName = null;
    public function getLocalName() : ?string
    {
        return $this->localName;
    }
    public function setLocalName(?string $localName) : void
    {
        $this->localName = $localName;
    }
}'
            ],
            [
                'Item',
                '<?php

declare (strict_types=1);
namespace App\Model;

class Item
{
    protected ?string $id = null;
    public function getId() : ?string
    {
        return $this->id;
    }
    public function setId(?string $id) : void
    {
        $this->id = $id;
    }
}'
            ],
            [
                'Same',
                '<?php

declare (strict_types=1);
namespace App\Model;

class Same
{
    protected float|int|null $length = null;
    protected ?Tag $tag = null;
    /**
     * @var array<Item>|null
     */
    protected ?array $items = null;
    public function getLength() : float|int|null
    {
        return $this->length;
    }
    public function setLength(float|int|null $length) : void
    {
        $this->length = $length;
    }
    public function getTag() : ?Tag
    {
        return $this->tag;
    }
    public function setTag(?Tag $tag) : void
    {
        $this->tag = $tag;
    }
    /**
     * @return array<Item>|null
     */
    public function getItems() : ?array
    {
        return $this->items;
    }
    /**
     * @param array<Item>|null $items
     */
    public function setItems(?array $items) : void
    {
        $this->items = $items;
    }
    public function addItem(Item $item) : self
    {
        $this->items[] = $item;
        return $this;
    }
}'
            ],
            [
                'Root',
                '<?php

declare (strict_types=1);
namespace App\Model;

class Root
{
    protected ?int $count = null;
    protected mixed $props = null;
    /**
     * @var array<string|bool>
     */
    protected array $levels = [];
    /**
     * @var array<Same>
     */
    protected array $same = [];
    public function getCount() : ?int
    {
        return $this->count;
    }
    public function setCount(?int $count) : void
    {
        $this->count = $count;
    }
    public function getProps() : mixed
    {
        return $this->props;
    }
    public function setProps(mixed $props) : void
    {
        $this->props = $props;
    }
    /**
     * @return array<string|bool>
     */
    public function getLevels() : array
    {
        return $this->levels;
    }
    /**
     * @param array<string|bool> $levels
     */
    public function setLevels(array $levels) : void
    {
        $this->levels = $levels;
    }
    public function addLevel(string|bool $level) : self
    {
        $this->levels[] = $level;
        return $this;
    }
    /**
     * @return array<Same>
     */
    public function getSame() : array
    {
        return $this->same;
    }
    /**
     * @param array<Same> $same
     */
    public function setSame(array $same) : void
    {
        $this->same = $same;
    }
    public function addSame(Same $same) : self
    {
        $this->same[] = $same;
        return $this;
    }
}'
            ],
        ];

        $converter = new Converter();
        $prettyPrinter = new PrettyPrinter\Standard();
        $ast = (new AstBuilder)->setNamespace($namespace);
        $classes = $converter->convert($json);

        foreach ($classes as $key => $class) {
            $this->assertEquals($expected[$key][0], $class->name);
            $this->assertEquals($expected[$key][1], $prettyPrinter->prettyPrintFile($ast->build($class)));
        }
    }
}
