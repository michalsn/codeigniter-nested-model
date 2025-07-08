<?php

declare(strict_types=1);

namespace Tests\Support\Models;

use CodeIgniter\Model;
use Michalsn\CodeIgniterNestedModel\Relation;
use Michalsn\CodeIgniterNestedModel\Traits\HasRelations;

class UuidProfileModel extends Model
{
    use HasRelations;

    protected $table         = 'uuid_profiles';
    protected $primaryKey    = 'id';
    protected $returnType    = 'object';
    protected $allowedFields = [
        'id', 'user_id', 'bio', 'website',
    ];

    protected function initialize()
    {
        $this->initRelations();
    }

    public function user(): Relation
    {
        return $this->belongsTo(UuidUserModel::class, 'user_id');
    }

    // Transform user IDs to binary for user relation
    protected function transformUserRelationIds(array $ids): array
    {
        return array_map(static fn ($id) => hex2bin(str_replace('-', '', $id)), $ids);
    }
}
