<?php

declare(strict_types=1);

namespace Tests\Support\Entities;

use CodeIgniter\Entity\Entity;

class Post extends Entity
{
    protected $datamap = [];
    protected $dates   = ['created_at', 'updated_at'];
    protected $casts   = [];
}
