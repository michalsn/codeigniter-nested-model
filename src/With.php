<?php

declare(strict_types=1);

namespace Michalsn\CodeIgniterNestedModel;

use Closure;

readonly class With
{
    public function __construct(public string $name, public ?Closure $closure = null)
    {
    }
}
