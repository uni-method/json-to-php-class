<?php declare(strict_types=1);

namespace UniMethod\JsonToPhpClass\Tests;

use JsonException;
use PHPUnit\Framework\TestCase;
use UniMethod\JsonToPhpClass\Converter;
use UniMethod\JsonToPhpClass\ConverterOptions;
use UniMethod\JsonToPhpClass\Model\NewClass;
use UniMethod\JsonToPhpClass\Model\NewClassProperty;

/**
 * @coversDefaultClass \UniMethod\JsonToPhpClass\Converter
 */
class ConverterOptionsTest extends TestCase
{
    /**
     * @return array<string, array<string, bool|string|NewClass>>
     */
    public function dataProviderOptions(): array
    {
        $json01 = '{"count": "150", "val": {"id": 1}}';

        $expected01 = new NewClass('Root');
        $expected01->addProperty(new NewClassProperty('count', 'string', 'string', 'count', true));
        $expected01->addProperty(new NewClassProperty('val', 'Val', 'Val', 'val', false));

        $expected02 = new NewClass('Root');
        $expected02->addProperty(new NewClassProperty('count', 'string', 'string', 'count', false));
        $expected02->addProperty(new NewClassProperty('val', 'Val', 'Val', 'val', true));

        $expected03 = new NewClass('Root');
        $expected03->addProperty(new NewClassProperty('count', 'string', 'string', 'count', true));
        $expected03->addProperty(new NewClassProperty('val', 'Val', 'Val', 'val', true));

        return [
            'Simple case: nullableScalarProperties = true' => [
                'json' => $json01,
                'nullableScalarProperties' => true,
                'nullableObjectProperties' => false,
                'expected' => $expected01
            ],
            'Simple case: nullableObjectProperties = true' => [
                'json' => $json01,
                'nullableScalarProperties' => false,
                'nullableObjectProperties' => true,
                'expected' => $expected02
            ],
            'Simple case: nullableScalarProperties = true and nullableObjectProperties = true' => [
                'json' => $json01,
                'nullableScalarProperties' => true,
                'nullableObjectProperties' => true,
                'expected' => $expected03
            ],
        ];
    }

    /**
     * @covers ::convert
     * @dataProvider dataProviderOptions
     * @throws JsonException
     */
    public function testNullableScalarProperties(string $json, bool $nullableScalarProperties, bool $nullableObjectProperties, object $expected): void
    {
        $converter = new Converter(new ConverterOptions($nullableScalarProperties, $nullableObjectProperties));
        self::assertEquals($expected, array_values(array_filter($converter->convert($json), static fn (NewClass $class) => $class->getName() === 'Root'))[0]);
    }
}