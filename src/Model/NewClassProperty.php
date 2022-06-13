<?php declare(strict_types=1);

namespace UniMethod\JsonToPhpClass\Model;

class NewClassProperty
{
    protected string $name;
    protected ?string $type;
    protected ?string $docType;
    protected string $originalName;

    public function __construct(string $name, ?string $type, ?string $docType, string $originalName)
    {
        $this->name = $name;
        $this->type = $type;
        $this->docType = $docType;
        $this->originalName = $originalName;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function getDocType(): ?string
    {
        return $this->docType;
    }

    public function getOriginalName(): string
    {
        return $this->originalName;
    }
}
