<?php declare(strict_types=1);

namespace UniMethod\JsonToPhpClass;

use PhpParser\{BuilderFactory, Node};
use UniMethod\JsonToPhpClass\Model\NewClass;
use UniMethod\JsonToPhpClass\Model\NewClassProperty;

class AstBuilder
{
    protected BuilderFactory $factory;

    protected ?string $namespace = null;

    public function __construct(?string $namespace)
    {
        $this->factory = new BuilderFactory;
        $this->namespace = $namespace;
    }

    public function build(NewClass $class): Node\Stmt
    {
        $node = $this->factory->class($class->getName());

        $isSerializedNameUsed = false;

        foreach ($class->getProperties() as $property) {
            $newProperty = $this->factory
                ->property($property->getName())
                ->makeProtected();

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

            if ($property->getType() !== null) {
                $newProperty->setType($this->getTypeWithNullableOption($property));
                $getter->setReturnType($this->getTypeWithNullableOption($property));
                $setter->addParam($this->factory->param($property->getName())->setType($this->getTypeWithNullableOption($property)));

                $propertyDocType = [];

                if ($property->getName() !== $property->getOriginalName()) {
                    $isSerializedNameUsed = true;
                    $propertyDocType[] = ' * @SerializedName("' . $property->getOriginalName() . '")';
                }

                if ($property->getType() !== $property->getDocType()) {
                    $propertyDocType[] = ' * @var ' . $this->getDocTypeWithNullableOption($property);

                    $getter->setDocComment('/**
                              * @return ' . $this->getDocTypeWithNullableOption($property) . '
                              */');

                    $setter->setDocComment('/**
                              * @param ' . $this->getDocTypeWithNullableOption($property) . ' $' . $property->getName() . '
                              */');
                }

                if ($propertyDocType !== []) {
                    $newProperty->setDocComment('/**' . PHP_EOL
                        . implode(PHP_EOL, $propertyDocType) . PHP_EOL .
                        ' */');
                }
            } else {
                $setter->addParam($this->factory->param($property->getName()));
            }

            $node->addStmts([$newProperty, $getter, $setter]);
        }

        $namespace = $this->factory->namespace($this->namespace);

        if ($isSerializedNameUsed) {
            $namespace->addStmt($this->factory->use('Symfony\Component\Serializer\Annotation\SerializedName'));
        }

        $namespace->addStmt($node);

        return $namespace->getNode();
    }

    protected function getGetter(string $name): string
    {
        return 'get' . ucfirst($name);
    }

    protected function getSetter(string $name): string
    {
        return 'set' . ucfirst($name);
    }

    protected function getTypeWithNullableOption(NewClassProperty $property): string {
        return ($property->isNullable() ? '?': '') . $property->getType();
    }

    protected function getDocTypeWithNullableOption(NewClassProperty $property): string {
        return $property->getDocType() . ($property->isNullable() ? '|null': '');
    }
}
