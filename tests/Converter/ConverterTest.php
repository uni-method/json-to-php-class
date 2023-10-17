<?php declare(strict_types=1);

namespace UniMethod\JsonToPhpClass\Tests\Converter;

use JsonException;
use LogicException;
use PHPUnit\Framework\TestCase;
use Throwable;
use UniMethod\JsonToPhpClass\Converter\Converter;
use UniMethod\JsonToPhpClass\Converter\Model\{_Class, _Simple, _SimpleCollection};

/**
 * @coversDefaultClass \UniMethod\JsonToPhpClass\Converter\Converter
 */
final class ConverterTest extends TestCase
{
    protected Converter $convert;

    /**
     * @return array<int, array<string, string>>
     */
    public function dataProviderConvertBasicTypes(): array
    {
        return [
            ['json' => '1', 'exception' => LogicException::class, 'errorMessage' => 'Can process only array, object root types'],
            ['json' => '0', 'exception' => LogicException::class, 'errorMessage' => 'Can process only array, object root types'],
            ['json' => 'true', 'exception' => LogicException::class, 'errorMessage' => 'Can process only array, object root types'],
            ['json' => 'false', 'exception' => LogicException::class, 'errorMessage' => 'Can process only array, object root types'],
            ['json' => 'null', 'exception' => LogicException::class, 'errorMessage' => 'Can process only array, object root types'],
            ['json' => ' ', 'exception' => JsonException::class, 'errorMessage' => 'Syntax error'],
            ['json' => '"hello"', 'exception' => LogicException::class, 'errorMessage' => 'Can process only array, object root types'],
            ['json' => '1.23', 'exception' => LogicException::class, 'errorMessage' => 'Can process only array, object root types'],
            ['json' => '0.0', 'exception' => LogicException::class, 'errorMessage' => 'Can process only array, object root types'],
            ['json' => '[]', 'exception' => LogicException::class, 'errorMessage' => 'Cannot convert empty array'],
            ['json' => '[1, 2]', 'exception' => LogicException::class, 'errorMessage' => 'Root array must contain only objects or array of objects'],
            ['json' => '[{}]', 'exception' => LogicException::class, 'errorMessage' => 'Empty object not allowed'],
            ['json' => '[{}, {}]', 'exception' => LogicException::class, 'errorMessage' => 'Empty object not allowed'],
        ];
    }

    /**
     * @covers ::convert
     * @dataProvider dataProviderConvertBasicTypes
     *
     * @param string $json
     * @param class-string<Throwable> $exception
     * @param string $errorMessage
     * @throws JsonException
     */
    public function testConvertBasicTypes(string $json, string $exception, string $errorMessage): void
    {
        $this->expectException($exception);
        $this->expectExceptionMessage($errorMessage);
        $this->convert->convert($json);
    }

    /**
     * @return array<int, array<string, string>>
     */
    public function dataProviderBorderRestrictions(): array
    {
        return [
            ['json' => '[{"a": "b"}, {"a": {"c": 1}}]', 'exception' => LogicException::class, 'errorMessage' => 'Property \'a\' cannot has simple and object type in the same time'],
        ];
    }

    /**
     * @covers ::convert
     * @dataProvider dataProviderBorderRestrictions
     *
     * @param string $json
     * @param class-string<Throwable> $exception
     * @param string $errorMessage
     * @throws JsonException
     */
    public function testBorderRestrictions(string $json, string $exception, string $errorMessage): void
    {
        $this->expectException($exception);
        $this->expectExceptionMessage($errorMessage);
        $this->convert->convert($json);
    }

    /**
     * @return array<string, array<string, string|_Class>>
     */
    public function dataProviderConvertObjects(): array
    {
        $simpleCase = new _Class('Root');
        $simpleCase->addSimpleType(new _Simple('abc', 'string', 'string', 'abc'));
        $simpleCase->addSimpleType(new _Simple('cdv', 'int', 'int', 'Cdv'));
        $simpleCase->addSimpleType(new _Simple('rty', 'float', 'float', 'rty'));
        $simpleCase->addSimpleType(new _Simple('asd', 'mixed', 'mixed', 'asd'));
        $simpleCase->addSimpleType(new _Simple('fgH', 'bool', 'bool', 'fgH'));

        $arrayCase = new _Class('Root');
        $arrayCase->addSimpleType(new _Simple('title', 'string', 'string', 'title'));

        $arrayWithNotExistedProperties = new _Class('Root');
        $arrayWithNotExistedProperties->addSimpleType(new _Simple('title', 'string', 'string', 'title'));
        $arrayWithNotExistedProperties->addSimpleType(new _Simple('isOk', 'bool', 'bool', 'isOk', true));

        $arrayWithDifferentTypesProperties = new _Class('Root');
        $arrayWithDifferentTypesProperties->addSimpleType(new _Simple('isAvailable', 'bool|string', 'bool|string', 'isAvailable'));
        $arrayWithDifferentTypesProperties->addSimpleType(new _Simple('isOk', 'bool', 'bool', 'isOk', true));

        $simpleArrayProperty = new _Class('Root');
        $simpleArrayProperty->addSimpleCollectionType(new _SimpleCollection(new _Simple('values', 'string', 'string', 'values'), 'value'));

        $simpleDifferentArrayProperty = new _Class('Root');
        $simpleDifferentArrayProperty->addSimpleCollectionType(new _SimpleCollection(new _Simple('values', 'string|int', 'string|int', 'values'), 'value'));

        $simpleEmptyArrayProperty = new _Class('Root');
        $simpleEmptyArrayProperty->addSimpleCollectionType(new _SimpleCollection(new _Simple('values', 'mixed', 'mixed', 'values'), 'value'));

        return [
            'simple case' => [
                'json' => '{"abc": "qwe", "Cdv": 123, "rty": 123.23, "asd": null, "fgH": true}',
                'expected' => $simpleCase,
            ],
            'root array with not empty objects' => [
                'json' => '[{"title": "hello world"}, {"title": ""}]',
                'expected' => $arrayCase,
            ],
            'root array with objects without properties' => [
                'json' => '[{"title": "hello world"}, {"title": "", "isOk": true}]',
                'expected' => $arrayWithNotExistedProperties,
            ],
            'root array with objects without properties, different order' => [
                'json' => '[{"title": "hello world", "isOk": true}, {"title": ""}]',
                'expected' => $arrayWithNotExistedProperties,
            ],
            'root array with different types properties' => [
                'json' => '[{"isAvailable": true, "isOk": true}, {"isAvailable": ""}]',
                'expected' => $arrayWithDifferentTypesProperties,
            ],
            'object with simple types array property' => [
                'json' => '{"values": ["hello ", "world"]}',
                'expected' => $simpleArrayProperty,
            ],
            'object with with different types of simple types array property' => [
                'json' => '{"values": ["1", 1]}',
                'expected' => $simpleDifferentArrayProperty,
            ],
            'object with empty array property' => [
                'json' => '{"values": []}',
                'expected' => $simpleEmptyArrayProperty,
            ],
        ];
    }

    /**
     * @covers ::convert
     * @dataProvider dataProviderConvertObjects
     *
     * @param string $json
     * @param object $expected
     * @throws JsonException
     */
    public function testConvertObjects(string $json, object $expected): void
    {
        self::assertEquals($expected, $this->convert->convert($json)[0]);
    }

    /**
     * @return array<string, array<string, array<int, string>|string>>
     */
    public function dataProviderNestedObjects(): array
    {
        return [
            'Nested array' => [
                'json' => '{"count": 150, "same": [{"length": 22.34}, {"length": 160.84}]}',
                'classNames' => ['Root', 'Same'],
            ],
            'Nested array with objects, simple name' => [
                'json' => '{"count": 150, "same": [{"length": 22.34, "tag": {"name": "zip"}}, {"length": 160.84}]}',
                'classNames' => ['Root', 'Same', 'Tag'],
            ],
            'Nested array with objects, complex name' => [
                'json' => '{"count": 150, "same": [{"length": 22.34, "reply_to_message": {"name": "zip"}}, {"length": 160.84}]}',
                'classNames' => ['Root', 'Same', 'ReplyToMessage'],
            ],
        ];
    }

    /**
     * @dataProvider dataProviderNestedObjects
     *
     * @param string $json
     * @param array<string> $classNames
     * @throws JsonException
     */
    public function testNestedObjects(string $json, array $classNames): void
    {
        self::assertEqualsCanonicalizing(
            $classNames,
            array_map(static fn(_Class $class) => $class->name, $this->convert->convert($json))
        );
    }

    protected function setUp(): void
    {
        $this->convert = new Converter();
    }
}
