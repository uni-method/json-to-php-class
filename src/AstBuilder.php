<?php declare(strict_types=1);

namespace UniMethod\JsonToPhpClass;

use PhpParser\{BuilderFactory, Node};
use UniMethod\JsonToPhpClass\Model\NewClass;

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
                $newProperty->setType($property->getType());
                $getter->setReturnType($property->getType());
                $setter->addParam($this->factory->param($property->getName())->setType($property->getType()));

                $propertyDocType = [];

                if ($property->getName() !== $property->getOriginalName()) {
                    $isSerializedNameUsed = true;
                    $propertyDocType[] = ' * @SerializedName("' . $property->getOriginalName() . '")';
                }

                if ($property->getType() !== $property->getDocType()) {
                    $propertyDocType[] = ' * @var ' . $property->getDocType();

                    $getter->setDocComment('/**
                              * @return ' . $property->getDocType() . '
                              */');

                    $setter->setDocComment('/**
                              * @param ' . $property->getDocType() . ' $' . $property->getName() . '
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
}
