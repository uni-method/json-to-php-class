<?php declare(strict_types=1);

namespace UniMethod\JsonToPhpClass\Converter\Model;

interface _Property
{
    public function getName(): string;

    public function getOriginalName(): string;

    public function isDifferentName(): bool;

    public function getType(): string;

    public function getDocType(): string;

    public function isTypeInDocDifferent(): bool;

    public function isNullable(): bool;

    public function setNullable(bool $status): void;

    public function isUndefined(): bool;
}
