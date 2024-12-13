<?php

declare(strict_types=1);

namespace Tests\Support\Entities;

use CodeIgniter\Entity\Entity;
use Michalsn\CodeIgniterNestedModel\Traits\HasLazyRelations;

class User extends Entity
{
    use HasLazyRelations;

    protected $datamap = [];
    protected $dates   = ['created_at', 'updated_at'];
    protected $casts   = [];
}
