<?php

declare(strict_types=1);

namespace Tests\Support\Models;

use CodeIgniter\Model;
use Michalsn\CodeIgniterNestedModel\Enums\OrderTypes;
use Michalsn\CodeIgniterNestedModel\Relation;
use Michalsn\CodeIgniterNestedModel\Traits\HasRelations;
use Tests\Support\Entities\User;

class UserModel extends Model
{
    use HasRelations;

    protected $table                  = 'users';
    protected $primaryKey             = 'id';
    protected $useAutoIncrement       = true;
    protected $returnType             = User::class;
    protected $useSoftDeletes         = false;
    protected $protectFields          = true;
    protected $allowedFields          = ['username'];
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

    public function profile(): Relation
    {
        return $this->hasOne(ProfileModel::class);
    }

    public function posts(): Relation
    {
        return $this->hasMany(PostModel::class);
    }

    public function latestPost(): Relation
    {
        return $this->hasOne(PostModel::class)->latestOfMany();
    }

    public function oldestPost(): Relation
    {
        return $this->hasOne(PostModel::class)->oldestOfMany();
    }

    public function bestPost(): Relation
    {
        return $this->hasOne(PostModel::class)->ofMany('rating', OrderTypes::DESC);
    }

    public function missingReturnType()
    {
    }

    public function incorrectReturnType(): string
    {
    }
}
