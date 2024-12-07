<?php

namespace Tests\Support\Entities;

use CodeIgniter\Entity\Entity;

class Student extends Entity
{
    protected $datamap = [];
    protected $dates   = ['created_at', 'updated_at'];
    protected $casts   = [];
}
