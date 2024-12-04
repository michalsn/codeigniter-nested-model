<?php

declare(strict_types=1);

namespace Michalsn\CodeIgniterNestedModel\Enums;

enum OrderTypes: string
{
    case ASC  = 'asc';
    case DESC = 'desc';

    public function sign(): string
    {
        return match ($this) {
            OrderTypes::ASC  => '>',
            OrderTypes::DESC => '<',
        };
    }
}
