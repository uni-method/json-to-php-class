<?php declare(strict_types=1);

namespace UniMethod\JsonToPhpClass;

class ConverterOptions
{
    protected bool $nullableScalarProperties;
    protected bool $nullableObjectProperties;

    public function __construct(bool $nullableScalarProperties = false, bool $nullableObjectProperties = false)
    {
        $this->nullableScalarProperties = $nullableScalarProperties;
        $this->nullableObjectProperties = $nullableObjectProperties;
    }

    public function isNullableScalarProperties(): bool
    {
        return $this->nullableScalarProperties;
    }

    public function isNullableObjectProperties(): bool
    {
        return $this->nullableObjectProperties;
    }
}