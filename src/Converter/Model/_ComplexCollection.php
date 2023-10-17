<?php declare(strict_types=1);

namespace UniMethod\JsonToPhpClass\Converter\Model;

use UniMethod\JsonToPhpClass\Converter\Converter;

class _ComplexCollection implements _Collection
{
    public function __construct(public _Complex $item, protected string $singleName)
    {
    }

    public function getName(): string
    {
        return $this->item->getName();
    }

    public function getOriginalName(): string
    {
        return $this->item->getOriginalName();
    }

    public function isDifferentName(): bool
    {
        return $this->getName() !== $this->getOriginalName();
    }

    public function getType(): string
    {
        return Converter::ARRAY;
    }

    public function getItemType(): string
    {
        return $this->item->getType();
    }

    public function getSingleName(): string
    {
        return $this->singleName;
    }

    public function getDocType(): string
    {
        return sprintf("array<%s>", $this->item->getDocType());
    }

    public function isTypeInDocDifferent(): bool
    {
        return $this->getType() !== $this->getDocType();
    }

    public function setUndefined(bool $status): void
    {
        $this->item->undefined = $status;
    }

    public function isNullable(): bool
    {
        return $this->item->isNullable();
    }

    public function setNullable(bool $status): void
    {
        $this->item->nullable = $status;
    }

    public function isUndefined(): bool
    {
        return $this->item->undefined;
    }
}
