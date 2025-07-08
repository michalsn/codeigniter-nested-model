<?php

declare(strict_types=1);

namespace Tests\Support\Models;

use CodeIgniter\Model;
use Michalsn\CodeIgniterNestedModel\Relation;
use Michalsn\CodeIgniterNestedModel\Traits\HasRelations;

class UuidUserModel extends Model
{
    use HasRelations;

    protected $table         = 'uuid_users';
    protected $primaryKey    = 'id';
    protected $returnType    = 'object';
    protected $allowedFields = [
        'id', 'username', 'email',
    ];

    protected function initialize()
    {
        $this->initRelations();
    }

    public function profile(): Relation
    {
        return $this->hasOne(UuidProfileModel::class, 'user_id');
    }

    public function posts(): Relation
    {
        return $this->hasMany(UuidPostModel::class, 'user_id');
    }

    // Transform UUIDs to binary for profile relation
    protected function transformProfileRelationIds(array $ids): array
    {
        return array_map(static fn ($id) => hex2bin(str_replace('-', '', $id)), $ids);
    }

    // Transform UUIDs to uppercase for posts relation
    protected function transformPostsRelationIds(array $ids): array
    {
        return array_map('strtoupper', $ids);
    }
}
