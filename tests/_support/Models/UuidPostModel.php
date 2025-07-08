<?php

declare(strict_types=1);

namespace Tests\Support\Models;

use CodeIgniter\Model;
use Michalsn\CodeIgniterNestedModel\Relation;
use Michalsn\CodeIgniterNestedModel\Traits\HasRelations;

class UuidPostModel extends Model
{
    use HasRelations;

    protected $table         = 'uuid_posts';
    protected $primaryKey    = 'id';
    protected $returnType    = 'object';
    protected $allowedFields = [
        'id', 'user_id', 'title', 'content',
    ];

    protected function initialize()
    {
        $this->initRelations();
    }

    public function user(): Relation
    {
        return $this->belongsTo(UuidUserModel::class, 'user_id');
    }

    public function comments(): Relation
    {
        return $this->hasMany(UuidCommentModel::class, 'post_id');
    }

    // Transform user IDs to uppercase for user relation
    protected function transformUserRelationIds(array $ids): array
    {
        return array_map('strtoupper', $ids);
    }

    // Transform post IDs to binary for comments relation
    protected function transformCommentsRelationIds(array $ids): array
    {
        return array_map(static fn ($id) => hex2bin(str_replace('-', '', $id)), $ids);
    }
}
