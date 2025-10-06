<?php

declare(strict_types=1);

namespace Tests\Support\Entities;

use CodeIgniter\Entity\Entity;

class Comment extends Entity
{
    protected $datamap = [
        'userId' => 'user_id',
    ];
    protected $dates = ['created_at', 'updated_at'];
    protected $casts = [];
}
