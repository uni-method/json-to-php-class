<?php declare(strict_types=1);

namespace UniMethod\JsonToPhpClass\Converter\Model;

class _Class
{
    /** @var _Simple[] */
    protected array $simpleTypes = [];

    /** @var _Complex[] */
    protected array $complexTypes = [];

    /** @var _SimpleCollection[] */
    protected array $simpleCollectionTypes = [];

    /** @var _ComplexCollection[] */
    protected array $complexCollectionTypes = [];

    public function __construct(public readonly string $name)
    {
    }

    public function addSimpleType(_Simple $simpleType): void
    {
        $this->simpleTypes[] = $simpleType;
    }

    /**
     * @return _Simple[]
     */
    public function getSimpleTypes(): array
    {
        return $this->simpleTypes;
    }

    public function addComplexType(_Complex $complexType): void
    {
        $this->complexTypes[] = $complexType;
    }

    /**
     * @return _Complex[]
     */
    public function getComplexTypes(): array
    {
        return $this->complexTypes;
    }

    public function addSimpleCollectionType(_SimpleCollection $simpleType): void
    {
        $this->simpleCollectionTypes[] = $simpleType;
    }

    /**
     * @return _SimpleCollection[]
     */
    public function getSimpleCollectionTypes(): array
    {
        return $this->simpleCollectionTypes;
    }

    public function addComplexCollectionType(_ComplexCollection $complexType): void
    {
        $this->complexCollectionTypes[] = $complexType;
    }

    /**
     * @return _ComplexCollection[]
     */
    public function getComplexCollectionTypes(): array
    {
        return $this->complexCollectionTypes;
    }
}
