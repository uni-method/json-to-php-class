<?php declare(strict_types=1);

namespace UniMethod\JsonToPhpClass\Builder;

use PhpParser\{BuilderFactory, Node, Node\Attribute, Node\Expr\Array_, Node\Name};
use UniMethod\JsonToPhpClass\Converter\Model\{_Class,
    _Collection,
    _Complex,
    _ComplexCollection,
    _Property,
    _Simple,
    _SimpleCollection};
use UniMethod\JsonToPhpClass\Builder\Model\Patch;
use UniMethod\JsonToPhpClass\Builder\Model\Scenarios;
use UniMethod\JsonToPhpClass\Converter\Converter;

class AstBuilder
{
    private BuilderFactory $factory;

    private Patcher $patcher;

    protected ?string $namespace = null;

    protected ?Scenarios $scenarios = null;

    /** @var array<Patch> */
    protected array $patches = [];

    public function __construct()
    {
        $this->factory = new BuilderFactory;
        $this->patcher = new Patcher;
    }

    public function setScenarios(Scenarios $scenarios): self
    {
        $this->scenarios = $scenarios;

        return $this;
    }

    public function setNamespace(string $namespace): self
    {
        $this->namespace = $namespace;

        return $this;
    }

    public function addPatch(Patch $patch): self
    {
        $this->patches[] = $patch;

        return $this;
    }

    /**
     * @return array<Node\Stmt>
     */
    public function build(_Class $class): array
    {
        $class = $this->patcher->applyPatches($class, $this->patches);

        $node = $this->factory->class($class->name);
        /** @var array<string> $uses */
        $uses = [];

        /** @var array<_Complex|_ComplexCollection|_Simple|_SimpleCollection> $properties */
        $properties = array_merge($class->getSimpleTypes(), $class->getComplexTypes(), $class->getSimpleCollectionTypes(), $class->getComplexCollectionTypes());

        foreach ($properties as $property) {
            $newProperty = $this->factory
                ->property($property->getName())
                ->makeProtected();

            if ($property instanceof _Collection && !$property->isUndefined()) {
                $newProperty->setDefault(new Array_([], ['kind' => Array_::KIND_SHORT]));
            } else {
                $newProperty->setDefault(new Node\Expr\ConstFetch(new Name('null')));
            }

            $getter = $this->factory
                ->method($this->getGetter($property->getName()))
                ->makePublic()
                ->addStmt(
                    new Node\Stmt\Return_(
                        new Node\Expr\PropertyFetch(
                            new Node\Expr\Variable('this'),
                            $property->getName()
                        )
                    )
                );

            $setter = $this->factory
                ->method($this->getSetter($property->getName()))
                ->makePublic()
                ->setReturnType('void')
                ->addStmt(
                    new Node\Stmt\Expression(
                        new Node\Expr\Assign(
                            new Node\Expr\PropertyFetch(new Node\Expr\Variable('this'), $property->getName()),
                            new Node\Expr\Variable($property->getName())
                        )
                    )
                );

            $newProperty->setType($this->getTypeWithNullableOptionForProperty($property));
            $getter->setReturnType($this->getTypeWithNullableOption($property));
            $setter->addParam($this->factory->param($property->getName())->setType($this->getTypeWithNullableOption($property)));

            /** @var array<string> $propertyComments */
            $propertyComments = [];
            /** @var array<Node\Attribute> $propertyAttributes */
            $propertyAttributes = [];

            if ($this->scenarios instanceof Scenarios) {
                if ($property->isDifferentName()) {
                    foreach ($this->extractAttributes($this->scenarios->attributesOnDifferentNames, $property) as $item) {
                        $propertyAttributes[] = $item;
                    }

                    foreach (array_keys($this->scenarios->attributesOnDifferentNames) as $use) {
                        $uses[] = $use;
                    }
                }

                if ($this->scenarios->attributesForNullAndUndefined !== []) {
                    if (!$property->isNullable() && !$property->isUndefined()) {
                        foreach ($this->extractAttributes($this->scenarios->attributesForNullAndUndefined[false][false], $property) as $item) {
                            $propertyAttributes[] = $item;
                        }

                        foreach (array_keys($this->scenarios->attributesForNullAndUndefined[false][false]) as $use) {
                            $uses[] = $use;
                        }
                    } elseif (!$property->isNullable() && $property->isUndefined()) {
                        foreach ($this->extractAttributes($this->scenarios->attributesForNullAndUndefined[false][true], $property) as $item) {
                            $propertyAttributes[] = $item;
                        }

                        foreach (array_keys($this->scenarios->attributesForNullAndUndefined[false][true]) as $use) {
                            $uses[] = $use;
                        }
                    } elseif ($property->isNullable() && !$property->isUndefined()) {
                        foreach ($this->extractAttributes($this->scenarios->attributesForNullAndUndefined[true][false], $property) as $item) {
                            $propertyAttributes[] = $item;
                        }

                        foreach (array_keys($this->scenarios->attributesForNullAndUndefined[true][false]) as $use) {
                            $uses[] = $use;
                        }
                    } elseif ($property->isNullable() && $property->isUndefined()) {
                        foreach ($this->extractAttributes($this->scenarios->attributesForNullAndUndefined[true][true], $property) as $item) {
                            $propertyAttributes[] = $item;
                        }

                        foreach (array_keys($this->scenarios->attributesForNullAndUndefined[true][true]) as $use) {
                            $uses[] = $use;
                        }
                    }
                }

                if ($this->scenarios->attributesForComplexAndComplexCollections !== []) {
                    if ($property instanceof _Complex || $property instanceof _ComplexCollection) {
                        foreach ($this->extractAttributes($this->scenarios->attributesForComplexAndComplexCollections, $property) as $item) {
                            $propertyAttributes[] = $item;
                        }

                        foreach (array_keys($this->scenarios->attributesForComplexAndComplexCollections) as $use) {
                            $uses[] = $use;
                        }
                    }
                }
            }

            if ($property->isTypeInDocDifferent()) {
                $propertyComments[] = ' * @var ' . $this->getDocTypeWithNullableOption($property);

                $getter->setDocComment('/**
                          * @return ' . $this->getDocTypeWithNullableOption($property) . '
                          */');

                $setter->setDocComment('/**
                          * @param ' . $this->getDocTypeWithNullableOption($property) . ' $' . $property->getName() . '
                          */');
            }

            if ($propertyComments !== []) {
                $newProperty->setDocComment('/**' . PHP_EOL
                    . implode(PHP_EOL, $propertyComments) . PHP_EOL .
                    ' */');
            }

            if ($propertyAttributes !== []) {
                foreach ($propertyAttributes as $propertyAttribute) {
                    $newProperty->addAttribute($propertyAttribute);
                }
            }

            $node->addStmts([$newProperty, $getter, $setter]);

            if ($property instanceof _Collection) {
                $add = $this->factory
                    ->method($this->getAdd($property->getSingleName()))
                    ->makePublic()
                    ->addParam($this->factory->param($property->getSingleName())->setType($property->getItemType()))
                    ->addStmt(
                        new Node\Stmt\Expression(
                            new Node\Expr\Assign(
                                new Node\Expr\ArrayDimFetch(
                                    new Node\Expr\PropertyFetch(
                                        new Node\Expr\Variable('this'),
                                        new Node\Identifier($property->getName())
                                    )
                                ),
                                new Node\Expr\Variable(
                                    $property->getSingleName()
                                )
                            )
                        )
                    )
                    ->addStmt(
                        new Node\Stmt\Return_(
                            new Node\Expr\Variable('this')
                        )
                    )
                    ->setReturnType(new Name('self'));

                $node->addStmt($add);
            }
        }

        $namespace = $this->factory->namespace($this->namespace);

        $uses = array_values(array_unique(array_filter($uses)));
        sort($uses);

        foreach ($uses as $use) {
            $namespace->addStmt($this->factory->use($use));
        }

        $namespace->addStmt($node);

        $strictTypes = new Node\Stmt\Declare_([
            new Node\Stmt\DeclareDeclare(
                new Node\Identifier('strict_types'),
                new Node\Scalar\LNumber(1)
            )
        ]);

        return [
            $strictTypes,
            $namespace->getNode()
        ];
    }

    protected function getGetter(string $name): string
    {
        return 'get' . ucfirst($name);
    }

    protected function getSetter(string $name): string
    {
        return 'set' . ucfirst($name);
    }

    protected function getAdd(string $name): string
    {
        return 'add' . ucfirst($name);
    }

    protected function getTypeWithNullableOptionForProperty(_Property $property): string
    {
        if ($property instanceof _Collection && !$property->isUndefined()) {
            return $property->getType();
        }

        if ($property->getType() === Converter::MIXED) {
            return $property->getType();
        }

        if (str_contains($property->getType(), '|')) {
            return $property->getType() . '|null';
        }

        return '?' . $property->getType();
    }

    protected function getTypeWithNullableOption(_Property $property): string
    {
        if ($property instanceof _Collection && !$property->isUndefined()) {
            return $property->getType();
        }

        if ($property->getType() === Converter::MIXED) {
            return $property->getType();
        }

        if (str_contains($property->getType(), '|')) {
            return $property->getType() . ($property->isNullable() || $property->isUndefined() ? '|null' : '');
        }

        return ($property->isNullable() || $property->isUndefined() ? '?' : '') . $property->getType();
    }

    protected function getDocTypeWithNullableOption(_Property $property): string
    {
        if ($property instanceof _Collection && !$property->isUndefined()) {
            return $property->getDocType();
        }

        if ($property->getType() === Converter::MIXED) {
            return $property->getType();
        }

        return $property->getDocType() . ($property->isNullable() || $property->isUndefined() ? '|null' : '');
    }

    /**
     * @param array<array<string, array<array<string|array<string, mixed>>>>> $attributesConfig
     * @param _Property $property
     * @return array<Attribute>
     */
    protected function extractAttributes(array $attributesConfig, _Property $property): array
    {
        $propertyAttributes = [];

        foreach ($attributesConfig as $attributes) {
            foreach ($attributes as $attribute) {
                if (isset($attribute[0]) && ($attribute[0] instanceof Name || is_string($attribute[0])) && (!isset($attribute[1]) || is_array($attribute[1]))) {
                    $params = [];

                    foreach ($attribute[1] ?? [] as $key => $value) {
                        $params[$key] = str_replace(['{{ originalName }}'], [$property->getOriginalName()], $value);
                    }

                    $propertyAttributes[] = $this->factory->attribute($attribute[0], $params);
                }
            }
        }

        return $propertyAttributes;
    }
}
