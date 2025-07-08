<?php

declare(strict_types=1);

namespace Tests\Support\Models;

use CodeIgniter\Model;
use Michalsn\CodeIgniterNestedModel\Relation;
use Michalsn\CodeIgniterNestedModel\Traits\HasRelations;

class UuidCommentModel extends Model
{
    use HasRelations;

    protected $table         = 'uuid_comments';
    protected $primaryKey    = 'id';
    protected $returnType    = 'object';
    protected $allowedFields = [
        'id', 'post_id', 'user_id', 'content',
    ];

    protected function initialize()
    {
        $this->initRelations();
    }

    public function user(): Relation
    {
        return $this->belongsTo(UuidUserModel::class, 'user_id');
    }

    public function post(): Relation
    {
        return $this->belongsTo(UuidPostModel::class, 'post_id');
    }

    // Transform user IDs to binary
    protected function transformUserRelationIds(array $ids): array
    {
        return array_map(static fn ($id) => hex2bin(str_replace('-', '', $id)), $ids);
    }

    // Transform post IDs to binary
    protected function transformPostRelationIds(array $ids): array
    {
        return array_map(static fn ($id) => hex2bin(str_replace('-', '', $id)), $ids);
    }
}
