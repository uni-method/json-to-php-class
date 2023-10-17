<?php declare(strict_types=1);

namespace UniMethod\JsonToPhpClass\Builder;

use UniMethod\JsonToPhpClass\Builder\Model\Patch;
use UniMethod\JsonToPhpClass\Converter\Model\_Class;

class Patcher
{
    /**
     * @param _Class $class
     * @param array<Patch> $patches
     * @return _Class
     */
    public function applyPatches(_Class $class, array $patches): _Class
    {
        $filtered = array_filter($patches, static function (Patch $patch) use ($class) {
            return $patch->className === $class->name;
        });

        foreach ($filtered as $patch) {
            foreach (array_merge($class->getSimpleTypes(), $class->getSimpleCollectionTypes(), $class->getComplexTypes(), $class->getSimpleCollectionTypes()) as $property) {
                if ($property->getName() === $patch->propertyName) {
                    if ($patch->nullable !== null) {
                        $property->setNullable($patch->nullable);
                    }
                }
            }
        }

        return $class;
    }
}
