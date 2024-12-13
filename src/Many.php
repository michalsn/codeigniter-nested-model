<?php

declare(strict_types=1);

namespace Michalsn\CodeIgniterNestedModel;

readonly class Many
{
    public function __construct(public string $pivotTable, public string $pivotForeignKey, public string $pivotRelatedKey)
    {
    }
}
