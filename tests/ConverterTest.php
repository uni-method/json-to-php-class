<?php declare(strict_types=1);

namespace UniMethod\JsonToPhpClass\Tests;

use JsonException;
use LogicException;
use PHPUnit\Framework\TestCase;
use Throwable;
use UniMethod\JsonToPhpClass\Converter;
use UniMethod\JsonToPhpClass\Model\NewClass;
use UniMethod\JsonToPhpClass\Model\NewClassProperty;

/**
 * @coversDefaultClass \UniMethod\JsonToPhpClass\Converter
 */
class ConverterTest extends TestCase
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
            ['json' => '[]', 'exception' => LogicException::class, 'errorMessage' => 'Root array must contain only objects or array of objects'],
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
     * @return array<string, array<string, string|NewClass>>
     */
    public function dataProviderConvertObjects(): array
    {
        $simpleCase = new NewClass('Root');
        $simpleCase->addProperty(new NewClassProperty('abc', 'string', 'string', 'abc'));
        $simpleCase->addProperty(new NewClassProperty('cdv', 'int', 'int', 'Cdv'));
        $simpleCase->addProperty(new NewClassProperty('rty', 'float', 'float', 'rty'));
        $simpleCase->addProperty(new NewClassProperty('asd', null, null, 'asd'));
        $simpleCase->addProperty(new NewClassProperty('fgH', 'bool', 'bool', 'fgH'));

        $arrayCase = new NewClass('Root');
        $arrayCase->addProperty(new NewClassProperty('title', 'string', 'string', 'title'));

        $arrayWithNotExistedProperties = new NewClass('Root');
        $arrayWithNotExistedProperties->addProperty(new NewClassProperty('title', 'string', 'string', 'title'));
        $arrayWithNotExistedProperties->addProperty(new NewClassProperty('isOk', 'bool', 'bool', 'isOk'));

        return [
            'simple case' => [
                'json' => '{"abc": "qwe", "Cdv": 123, "rty": 123.23, "asd": null, "fgH": true}',
                'expected' => $simpleCase,
            ],
            'root array with no empty objects' => [
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
            array_map(static fn(NewClass $class) => $class->getName(), $this->convert->convert($json))
        );
    }

    protected function setUp(): void
    {
        $this->convert = new Converter();
    }
}
