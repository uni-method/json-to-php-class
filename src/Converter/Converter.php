<?php declare(strict_types=1);

namespace UniMethod\JsonToPhpClass\Converter;

use JsonException;
use LogicException;
use UniMethod\JsonToPhpClass\Converter\Model\{_Class, _Collection, _Complex, _ComplexCollection, _Simple, _SimpleCollection};

class Converter
{
    public const ARRAY = 'array';
    public const OBJECT = 'object';

    public const BOOLEAN = 'boolean';
    public const INTEGER = 'integer';
    public const FLOAT = 'double';
    public const STRING = 'string';

    public const MIXED = 'mixed';
    public const NULL = 'NULL';

    public const APPROVED_ROOT_TYPES = [self::ARRAY, self::OBJECT];

    public const SIMPLE_MAPPING_TYPES = [
        self::INTEGER => 'int',
        self::STRING => 'string',
        self::BOOLEAN => 'bool',
        self::FLOAT => 'float',
    ];

    public const ROOT_CLASS_NAME = 'Root';

    /**
     * @var _Class[]
     */
    protected array $classes = [];

    public function __construct()
    {
    }

    /**
     * @param string $json
     * @return _Class[]
     * @throws JsonException
     */
    public function convert(string $json): array
    {
        $data = json_decode($json, false, 512, JSON_THROW_ON_ERROR);

        if ($data === []) {
            throw new LogicException('Cannot convert empty array');
        }

        $dataType = $this->getPhpTypeByJsonValue($data);

        switch ($dataType) {
            case self::ARRAY:
                $propertyName = ''; // apex

                /** @var array<object|mixed> $data */
                $collection = $this->processArrayAndReturnDocType($data, self::ROOT_CLASS_NAME, $propertyName);

                if ($collection instanceof _SimpleCollection) {
                    throw new LogicException('Root array must contain only objects or array of objects');
                }

                break;
            case self::OBJECT:
                /** @var object $data */
                $class = $this->processObject(self::ROOT_CLASS_NAME, $data);

                if ($class instanceof _Class) {
                    $this->classes[] = $class;
                }

                break;
            default:
                throw new LogicException(sprintf(
                    'Can process only %s root types',
                    implode(', ', self::APPROVED_ROOT_TYPES)
                ));
        }

        if ($this->classes === []) {
            throw new LogicException('Empty object not allowed');
        }

        return $this->classes;
    }

    protected function processObject(string $className, object ...$objs): ?_Class
    {
        /** @var array<string, array<string>> $usedSimpleTypesForProperty */
        $usedSimpleTypesForProperty = [];

        /** @var array<string, _Simple> $simpleTypes */
        $simpleTypes = [];
        /** @var array<string, _Complex> $complexTypes */
        $complexTypes = [];

        /** @var array<string, _SimpleCollection> $simpleCollectionTypes */
        $simpleCollectionTypes = [];
        /** @var array<string, _ComplexCollection> $complexCollectionTypes */
        $complexCollectionTypes = [];

        if (count($objs) > 1) {
            $definedProperties = array_intersect(...array_map(static fn (object $obj) => array_keys(get_object_vars($obj)), $objs));
        } else {
            $definedProperties = array_keys(get_object_vars($objs[0] ?? (object)[]));
        }

        foreach ($objs as $obj) {
            foreach (get_object_vars($obj) as $propertyName => $value) {
                $valueType = $this->getPhpTypeByJsonValue($value);

                $isUndefined = !in_array($propertyName, $definedProperties, true);

                if (array_key_exists($valueType, self::SIMPLE_MAPPING_TYPES)) {
                    $phpType = self::SIMPLE_MAPPING_TYPES[$valueType];

                    if (!isset($usedSimpleTypesForProperty[$propertyName]) || !in_array($phpType, $usedSimpleTypesForProperty[$propertyName], true)) {
                        $usedSimpleTypesForProperty[$propertyName][] = $phpType;
                    }

                    $phpType = implode('|', $usedSimpleTypesForProperty[$propertyName]);
                    $docType = $phpType;
                } elseif ($valueType === self::ARRAY) {
                    if (isset($simpleTypes[$propertyName])) {
                        throw new LogicException(sprintf(
                            'Property \'%s\' cannot has simple and any array type in the same time',
                            $propertyName
                        ));
                    }

                    $childClassName = $this->getClassNameFromType($propertyName);
                    $collection = $this->processArrayAndReturnDocType($value, $childClassName, $propertyName);
                    $collection->setUndefined($isUndefined);

                    if ($collection instanceof _SimpleCollection) {
                        $simpleCollectionTypes[$propertyName] = $collection;
                    } else {
                        $complexCollectionTypes[$propertyName] = $collection;
                    }

                    continue;
                } elseif ($valueType === self::OBJECT) {
                    if (isset($simpleTypes[$propertyName])) {
                        throw new LogicException(sprintf(
                            'Property \'%s\' cannot has simple and object type in the same time',
                            $propertyName
                        ));
                    }

                    $newClassName = $this->getClassNameFromType($propertyName);
                    $class = $this->processObject($newClassName, $value);

                    if ($class instanceof _Class) {
                        $this->classes[] = $class;

                        if (!isset($complexTypes[$propertyName]) || !$complexTypes[$propertyName] instanceof _Complex) {
                            $complexTypes[$propertyName] = new _Complex($this->getPropertyName($propertyName), $newClassName, $propertyName, $isUndefined);
                        }
                    }

                    continue;
                } elseif ($valueType === self::NULL) {
                    $phpType = self::MIXED;
                    $docType = self::MIXED;

                    if (isset($complexTypes[$propertyName]) && $complexTypes[$propertyName] instanceof _Complex) {
                        $complexTypes[$propertyName]->nullable = true;
                        continue;
                    }
                } else {
                    throw new LogicException(sprintf(
                        'Cannot process %s type',
                        $valueType
                    ));
                }

                if (!isset($simpleTypes[$propertyName]) || !$simpleTypes[$propertyName] instanceof _Simple) {
                    $simpleTypes[$propertyName] = new _Simple($this->getPropertyName($propertyName), $phpType, $docType, $propertyName, $isUndefined);
                } elseif ($simpleTypes[$propertyName]->phpType !== $phpType || $simpleTypes[$propertyName]->docType !== $docType) {
                    $simpleTypes[$propertyName]->phpType = $phpType;
                    $simpleTypes[$propertyName]->docType = $docType;
                }
            }
        }

        if (count($simpleTypes) === 0 && count($complexTypes) === 0 && count($simpleCollectionTypes) === 0 & count($complexCollectionTypes) === 0) {
            return null;
        }

        $class = new _Class($className);

        foreach ($simpleTypes as $simpleType) {
            $class->addSimpleType($simpleType);
        }

        foreach ($complexTypes as $complexType) {
            $class->addComplexType($complexType);
        }

        foreach ($simpleCollectionTypes as $simpleCollectionType) {
            $class->addSimpleCollectionType($simpleCollectionType);
        }

        foreach ($complexCollectionTypes as $complexCollectionType) {
            $class->addComplexCollectionType($complexCollectionType);
        }

        return $class;
    }

    /**
     * php type is poor for describing collection types
     *
     * @param array<object|mixed> $objects
     * @param string $className
     * @param string $propertyName
     * @return _Collection
     */
    protected function processArrayAndReturnDocType(array $objects, string $className, string $propertyName): _Collection
    {
        $futurePropertyName = $this->getPropertyName($propertyName);

        if ($objects === []) {
            return new _SimpleCollection(
                new _Simple($futurePropertyName, self::MIXED, self::MIXED, $propertyName),
                $this->removingThePlural($futurePropertyName)
            );
        }

        $allTypes = $this->getTypesInArray($objects);

        if ($this->isContainOnlyObjects($allTypes) === false) {
            if ($this->isContainOnlySimpleTypes($allTypes)) {

                $types = implode('|', array_map(static function (string $phpType) {
                    return self::SIMPLE_MAPPING_TYPES[$phpType];
                }, $allTypes));
            } else {
                $types = self::ARRAY;
            }

            return new _SimpleCollection(
                new _Simple($futurePropertyName, $types, $types, $propertyName),
                $this->removingThePlural($futurePropertyName)
            );
        }

        /** @var array<object> $objects */
        $className = $this->removingThePlural($className);
        $class = $this->processObject($className, ...$objects);

        if ($class instanceof _Class) {
            $this->classes[] = $class;
        }

        return new _ComplexCollection(
            new _Complex($futurePropertyName, $className, $propertyName),
            $this->removingThePlural($futurePropertyName)
        );
    }

    /**
     * @param array<string> $types
     * @return bool
     */
    protected function isContainOnlyObjects(array $types): bool
    {
        return in_array(self::OBJECT, $types, true);
    }

    /**
     * @param array<string> $types
     * @return bool
     */
    protected function isContainOnlySimpleTypes(array $types): bool
    {
        foreach ($types as $type) {
            if (!array_key_exists($type, self::SIMPLE_MAPPING_TYPES)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param array<mixed> $values
     * @return array<string>
     */
    protected function getTypesInArray(array $values): array
    {
        return array_unique(array_map('gettype', $values));
    }

    protected function getPhpTypeByJsonValue(mixed $value): string
    {
        return gettype($value);
    }

    protected function getClassNameFromType(string $type): string
    {
        return ucfirst($this->getCamelCase($type));
    }

    protected function getPropertyName(string $property): string
    {
        return $this->getCamelCase($property);
    }

    protected function getCamelCase(string $str): string
    {
        $str = str_replace(['-', '_'], ' ', $str);
        $str = str_replace(' ', '', ucwords($str));
        return lcfirst($str);
    }

    protected function removingThePlural(string $name): string
    {
        if (mb_strlen($name) > 1 && mb_substr($name, -1) === 's') {
            return mb_substr($name, 0, -1);
        }

        return $name;
    }
}
