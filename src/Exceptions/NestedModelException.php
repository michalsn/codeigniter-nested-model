<?php

declare(strict_types=1);

namespace Michalsn\CodeIgniterNestedModel\Exceptions;

use RuntimeException;

final class NestedModelException extends RuntimeException
{
    public static function forMethodNotSupported(string $name): static
    {
        return new self(lang('NestedModel.methodNotSupported', [$name]));
    }

    public static function forParentRelationNotDeclared(string $name): static
    {
        return new self(lang('NestedModel.parentRelationNotDeclared', [$name]));
    }

    public static function forRelationNotDefined(string $name): static
    {
        return new self(lang('NestedModel.relationNotDefined', [$name]));
    }

    public static function forMissingReturnType(string $name): static
    {
        return new self(lang('NestedModel.missingReturnType', [$name]));
    }

    public static function forIncorrectReturnType(string $name): static
    {
        return new self(lang('NestedModel.incorrectReturnType', [$name]));
    }

    public static function forRelationDoesNotSupportWrite(): static
    {
        return new self(lang('NestedModel.relationDoesNotSupportWrite'));
    }
}
