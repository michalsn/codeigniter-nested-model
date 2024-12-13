<?php

declare(strict_types=1);

namespace Tests\Support\Entities;

use CodeIgniter\Entity\Entity;

class Profile extends Entity
{
    protected $datamap = [];
    protected $dates   = [];
    protected $casts   = [];
}
