<?php

declare(strict_types=1);

namespace Tests\Support\Models;

use CodeIgniter\Model;
use Michalsn\CodeIgniterNestedModel\Relation;
use Michalsn\CodeIgniterNestedModel\Traits\HasRelations;
use Tests\Support\Entities\Post;

class PostModel extends Model
{
    use HasRelations;

    protected $table                  = 'posts';
    protected $primaryKey             = 'id';
    protected $useAutoIncrement       = true;
    protected $returnType             = Post::class;
    protected $useSoftDeletes         = false;
    protected $protectFields          = true;
    protected $allowedFields          = ['user_id', 'title', 'content', 'rating'];
    protected bool $allowEmptyInserts = false;
    protected bool $updateOnlyChanged = true;
    protected array $casts            = [];
    protected array $castHandlers     = [];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    // Validation
    protected $validationRules      = [];
    protected $validationMessages   = [];
    protected $skipValidation       = false;
    protected $cleanValidationRules = true;

    // Callbacks
    protected $allowCallbacks = true;
    protected $beforeInsert   = [];
    protected $afterInsert    = [];
    protected $beforeUpdate   = [];
    protected $afterUpdate    = [];
    protected $beforeFind     = [];
    protected $afterFind      = [];
    protected $beforeDelete   = [];
    protected $afterDelete    = [];

    protected function initialize(): void
    {
        $this->initRelations();
    }

    public function user(): Relation
    {
        return $this->belongsTo(UserModel::class);
    }

    public function comments(): Relation
    {
        return $this->hasMany(CommentModel::class);
    }
}
