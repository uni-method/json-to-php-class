<?php declare(strict_types=1);

namespace UniMethod\JsonToPhpClass;

use JsonException;
use LogicException;
use UniMethod\JsonToPhpClass\Model\{NewClass, NewClassProperty};
use stdClass;

class Converter
{
    private const ARRAY = 'array';
    private const OBJECT = 'object';

    private const BOOLEAN = 'boolean';
    private const INTEGER = 'integer';
    private const FLOAT = 'double';
    private const STRING = 'string';

    private const NULL = 'NULL';

    private const APPROVED_ROOT_TYPES = [self::ARRAY, self::OBJECT];

    private const SIMPLE_MAPPING_TYPES = [
        self::INTEGER => 'int',
        self::STRING => 'string',
        self::BOOLEAN => 'bool',
        self::FLOAT => 'float',
    ];

    private const ROOT_CLASS_NAME = 'Root';

    /**
     * @var NewClass[]
     */
    protected array $classes = [];

    /**
     * @param string $json
     * @return NewClass[]
     * @throws JsonException
     */
    public function convert(string $json): array
    {
        $data = json_decode($json, false, 512, JSON_THROW_ON_ERROR);

        $dataType = $this->getPhpTypeByJsonValue($data);

        switch ($dataType) {
            case self::ARRAY:
                /** @var array<object> $data */
                $type = $this->processArrayAndReturnDocType($data, self::ROOT_CLASS_NAME);

                if ($type === self::ARRAY) {
                    throw new LogicException('Root array must contain only objects or array of objects');
                }

                break;
            case self::OBJECT:
                /** @var object $data */
                $this->processObject($data, self::ROOT_CLASS_NAME);
                break;
            default:
                throw new LogicException(sprintf(
                    'Can process only %s root types',
                    implode(', ', self::APPROVED_ROOT_TYPES)
                ));
        }

        return $this->classes;
    }

    protected function processObject(object $obj, string $className): void
    {
        $class = $this->createClass($className);

        $asArray = (array) $obj;

        if (count($asArray) === 0) {
            throw new LogicException('Empty object not allowed');
        }

        foreach ($asArray as $name => $value) {
            $valueType = $this->getPhpTypeByJsonValue($value);

            if (array_key_exists($valueType, self::SIMPLE_MAPPING_TYPES)) {
                $propertyType = self::SIMPLE_MAPPING_TYPES[$valueType];
                $docType = $propertyType;
            } elseif ($valueType === self::ARRAY) {
                $propertyType = self::ARRAY;
                $childClassName = $this->getClassNameFromType($name);
                $docType = $this->processArrayAndReturnDocType($value, $childClassName);
            } elseif ($valueType === self::OBJECT) {
                $propertyType = $this->getClassNameFromType($name);
                $docType = $propertyType;
                $this->processObject($value, $propertyType);
            } elseif ($valueType === self::NULL) {
                $propertyType = null;
                $docType = null;
            } else {
                throw new LogicException(sprintf(
                    'Cannot process %s type',
                    $valueType
                ));
            }

            $this->addProperty($class, $name, $propertyType, $docType);
        }

        $this->classes[] = $class;
    }

    /**
     * @param array<mixed> $objects
     * @param string $className
     * @return string
     */
    protected function processArrayAndReturnDocType(array $objects, string $className): string
    {
        $containOnlyObjects = $this->isContainOnlyObjects($objects);

        if ($containOnlyObjects === false) {
            return self::ARRAY;
        }

        /** @var array<object> $objects */
        $objectWithAllProperties = $this->combineObjects($objects);
        $this->processObject($objectWithAllProperties, $className);

        return $className . '[]';
    }

    /**
     * @param array<object> $objects
     * @return object
     */
    protected function combineObjects(array $objects): object
    {
        $result = array_merge(...array_map([$this, 'toArray'], $objects));

        return (object) $result;
    }

    /**
     * @param stdClass $object
     * @return array<string, mixed>
     */
    protected function toArray(stdClass $object): array
    {
        return (array) $object;
    }

    /**
     * @param array<mixed> $values
     * @return bool
     */
    protected function isContainOnlyObjects(array $values): bool
    {
        return in_array(self::OBJECT, array_unique(array_map('gettype', $values)), true);
    }

    /**
     * @param mixed|null $value
     * @return string
     */
    protected function getPhpTypeByJsonValue($value): string
    {
        return gettype($value);
    }

    protected function createClass(string $name): NewClass
    {
        return new NewClass($name);
    }

    protected function addProperty(NewClass $class, string $name, ?string $type, ?string $docType): void
    {
        $class->addProperty(
            new NewClassProperty($this->getPropertyName($name), $type, $docType, $name)
        );
    }

    protected function getClassNameFromType(string $type): string
    {
        return ucfirst($type);
    }

    protected function getPropertyName(string $str): string
    {
        $str = str_replace(['-', '_'], ' ', $str);
        $str = str_replace(' ', '', ucwords($str));
        return lcfirst($str);
    }
}

