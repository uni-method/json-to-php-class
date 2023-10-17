<?php declare(strict_types=1);

namespace UniMethod\JsonToPhpClass\Converter\Model;

class _Complex implements _Property
{
    public function __construct(
        public string $propertyName,
        public string $className,
        public string $originalName,
        public bool   $undefined = false,
        public bool   $nullable = true
    )
    {
    }

    public function getName(): string
    {
        return $this->propertyName;
    }

    public function getOriginalName(): string
    {
        return $this->originalName;
    }

    public function isDifferentName(): bool
    {
        return $this->getName() !== $this->getOriginalName();
    }

    public function getType(): string
    {
        return $this->className;
    }

    public function getDocType(): string
    {
        return $this->className;
    }

    public function isTypeInDocDifferent(): bool
    {
        return $this->getType() !== $this->getDocType();
    }

    public function isNullable(): bool
    {
        return $this->nullable;
    }

    public function setNullable(bool $status): void
    {
        $this->nullable = $status;
    }

    public function isUndefined(): bool
    {
        return $this->undefined;
    }
}
