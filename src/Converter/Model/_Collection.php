<?php declare(strict_types=1);

namespace UniMethod\JsonToPhpClass\Converter\Model;

interface _Collection extends _Property
{
    public function setUndefined(bool $status): void;

    public function getItemType(): string;

    public function getSingleName(): string;
}
