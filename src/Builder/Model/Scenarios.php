<?php declare(strict_types=1);

namespace UniMethod\JsonToPhpClass\Builder\Model;

final class Scenarios
{
    /** @var array<bool, bool, array<string, array<array<string|array<string, mixed>>>>> */
    public array $attributesForNullAndUndefined = [];

    /** @var array<string, array<array<string|array<string, mixed>>>>  */
    public array $attributesOnDifferentNames = [];

    /** @var array<string, array<array<string|array<string, mixed>>>>  */
    public array $attributesForComplexAndComplexCollections = [];
}
