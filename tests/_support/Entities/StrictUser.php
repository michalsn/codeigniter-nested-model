<?php

declare(strict_types=1);

namespace Tests\Support\Entities;

use Michalsn\CodeIgniterNestedModel\Traits\HasLazyRelations;

class StrictUser extends StrictEntity
{
    use HasLazyRelations;

    protected $attributes = [
        'id'         => null,
        'username'   => null,
        'company_id' => null,
        'country_id' => null,
        'created_at' => null,
        'updated_at' => null,
    ];
    protected $datamap = [];
    protected $dates   = ['created_at', 'updated_at'];
    protected $casts   = [];
}
