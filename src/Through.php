<?php

declare(strict_types=1);

namespace Michalsn\CodeIgniterNestedModel;

use CodeIgniter\Model;

readonly class Through
{
    public function __construct(public Model $model, public string $foreignKey, public string $primaryKey)
    {
    }
}
