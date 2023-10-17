<?php declare(strict_types=1);

namespace UniMethod\JsonToPhpClass\Builder\Model;

final readonly class Patch
{
    public function __construct(public string $className, public string $propertyName, public ?bool $nullable = null)
    {
    }
}
