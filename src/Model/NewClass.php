<?php declare(strict_types=1);

namespace UniMethod\JsonToPhpClass\Model;

class NewClass
{
    protected string $name;
    /**
     * @var NewClassProperty[]
     */
    protected array $properties = [];

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    public function addProperty(NewClassProperty $property): void
    {
        $this->properties[] = $property;
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return NewClassProperty[]
     */
    public function getProperties(): array
    {
        return $this->properties;
    }
}
