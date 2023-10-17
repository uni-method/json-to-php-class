<?php declare(strict_types=1);

namespace UniMethod\JsonToPhpClass\Tests\Builder\Model;

use JsonException;
use PhpParser\PrettyPrinter;
use PHPUnit\Framework\TestCase;
use UniMethod\JsonToPhpClass\Builder\AstBuilder;
use UniMethod\JsonToPhpClass\Builder\Model\Patch;
use UniMethod\JsonToPhpClass\Builder\Model\Scenarios;
use UniMethod\JsonToPhpClass\Converter\Converter;

final class ScenariosTest extends TestCase
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
  "same": [
    {
      "length": 22.34,
      "tag": {
        "local_name": "zip"
      }
    },
    {
      "length": 160
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

use Symfony\Component\Serializer\Annotation\SerializedName;
class Tag
{
    #[SerializedName(\'local_name\')]
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
                'Same',
                '<?php

declare (strict_types=1);
namespace App\Model;

use Symfony\Component\Validator\Constraints as Assert;
class Same
{
    protected float|int|null $length = null;
    #[Assert\Valid]
    protected ?Tag $tag = null;
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
}'
            ],
            [
                'Root',
                '<?php

declare (strict_types=1);
namespace App\Model;

use Symfony\Component\Validator\Constraints as Assert;
class Root
{
    #[Assert\NotNull]
    protected ?int $count = null;
    /**
     * @var array<string|bool>
     */
    protected array $levels = [];
    /**
     * @var array<Same>
     */
    #[Assert\Valid]
    protected array $same = [];
    public function getCount() : int
    {
        return $this->count;
    }
    public function setCount(int $count) : void
    {
        $this->count = $count;
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
        $scenarios = new Scenarios;
        $scenarios->attributesOnDifferentNames = [
            'Symfony\Component\Serializer\Annotation\SerializedName' => [['SerializedName', ['{{ originalName }}']]]
        ];
        $scenarios->attributesForComplexAndComplexCollections = [
            'Symfony\Component\Validator\Constraints as Assert' => [['Assert\Valid']]
        ];
        $scenarios->attributesForNullAndUndefined = [
            false => [
                false => [
                    'Symfony\Component\Validator\Constraints as Assert' => [['Assert\NotNull']]
                ],
                true => [],
            ],
            true => [
                false => [],
                true => [],
            ],
        ];

        $ast = (new AstBuilder)
            ->setNamespace($namespace)
            ->setScenarios($scenarios)
            ->addPatch(new Patch('Root', 'count', false));
        $classes = $converter->convert($json);

        foreach ($classes as $key => $class) {
            $this->assertEquals($expected[$key][0], $class->name);
            $this->assertEquals($expected[$key][1], $prettyPrinter->prettyPrintFile($ast->build($class)));
        }
    }
}
