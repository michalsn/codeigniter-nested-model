<?php

declare(strict_types=1);

namespace Michalsn\CodeIgniterNestedModel\Traits;

use Closure;
use CodeIgniter\Database\Exceptions\DatabaseException;
use CodeIgniter\Database\Exceptions\DataException;
use CodeIgniter\Model;
use LogicException;
use Michalsn\CodeIgniterNestedModel\Enums\RelationTypes;
use Michalsn\CodeIgniterNestedModel\Exceptions\NestedModelException;
use Michalsn\CodeIgniterNestedModel\Relation;
use Michalsn\CodeIgniterNestedModel\With;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use ReflectionNamedType;

trait HasRelations
{
    private array $relations      = [];
    private array $relationErrors = [];
    private bool $useTransactions = false;

    /**
     * Set up model events and initialize
     * relation model stuff.
     */
    protected function initRelations(): void
    {
        $this->beforeInsert[] = 'relationsBeforeInsert';
        $this->afterInsert[]  = 'relationsAfterInsert';
        $this->beforeUpdate[] = 'relationsBeforeUpdate';
        $this->afterUpdate[]  = 'relationsAfterUpdate';
        $this->afterFind[]    = 'relationsAfterFind';

        helper('inflector');
    }

    /**
     * Set the relation to use.
     */
    public function with(string $relation, ?Closure $closure = null): static
    {
        if (str_contains($relation, '.')) {
            [$relation, $name] = explode('.', $relation, 2);

            if (! isset($this->relations[$relation])) {
                throw NestedModelException::forParentRelationNotDeclared($relation);
            }

            $this->relations[$relation]->setWith(new With($name, $closure));

            return $this;
        }

        $this->checkReturnType($relation);

        $this->{$relation}();

        if ($closure !== null) {
            $this->relations[$relation]->setConditions($closure);
        }

        return $this;
    }

    /**
     * Validate relation definition.
     */
    private function checkReturnType(string $methodName): bool
    {
        if (! method_exists($this, $methodName)) {
            throw NestedModelException::forRelationNotDefined($methodName);
        }

        $reflectionMethod = new ReflectionMethod($this, $methodName);
        $returnType       = $reflectionMethod->getReturnType();

        if (! $returnType instanceof ReflectionNamedType) {
            throw NestedModelException::forMissingReturnType($methodName);
        }

        if ($returnType->getName() !== Relation::class) {
            throw NestedModelException::forIncorrectReturnType($methodName);
        }

        return true;
    }

    /**
     * @throws ReflectionException
     */
    private function addRelation(Model|string $model, RelationTypes $relationType, ?string $foreignKey = null, ?string $primaryKey = null): Relation
    {
        $relation              = $this->getInitialMethodName();
        $this->allowedFields[] = $relation;

        $model = $this->getModelInstance($model);

        $this->relations[$relation] = new Relation(
            $relationType,
            $model,
            $foreignKey ?? ($relationType === RelationTypes::belongsTo ? get_primary_key($model) : get_foreign_key($this)),
            $primaryKey ?? ($relationType === RelationTypes::belongsTo ? get_foreign_key($model) : get_primary_key($this)),
        );

        // dd($this->relations[$relation]->foreignKey, $this->relations[$relation]->primaryKey);
        return $this->relations[$relation];
    }

    /**
     * @throws ReflectionException
     */
    protected function hasOne(Model|string $model, ?string $foreignKey = null, ?string $primaryKey = null): Relation
    {
        return $this->addRelation($model, RelationTypes::hasOne, $foreignKey, $primaryKey);
    }

    /**
     * @throws ReflectionException
     */
    protected function hasMany(Model|string $model, ?string $foreignKey = null, ?string $primaryKey = null): Relation
    {
        return $this->addRelation($model, RelationTypes::hasMany, $foreignKey, $primaryKey);
    }

    /**
     * @throws ReflectionException
     */
    protected function belongsTo(Model|string $model, ?string $primaryKey = null, ?string $foreignKey = null): Relation
    {
        return $this->addRelation($model, RelationTypes::belongsTo, $foreignKey, $primaryKey);
    }

    /**
     * @throws ReflectionException
     */
    protected function hasOneThrough(
        Model|string $model,
        Model|string $through,
        ?string $throughForeignKey = null,
        ?string $foreignKey = null,
        ?string $throughPrimaryKey = null,
        ?string $primaryKey = null,
    ): Relation {
        $model   = $this->getModelInstance($model);
        $through = $this->getModelInstance($through);

        $foreignKey ??= get_foreign_key($through);
        $throughForeignKey ??= get_foreign_key($model);

        return $this->addRelation($model, RelationTypes::hasOne, $foreignKey, $primaryKey)
            ->setThrough($through, $throughForeignKey, $throughPrimaryKey);
    }

    /**
     * @throws ReflectionException
     */
    protected function hasManyThrough(
        Model|string $model,
        Model|string $through,
        ?string $throughForeignKey = null,
        ?string $foreignKey = null,
        ?string $throughPrimaryKey = null,
        ?string $primaryKey = null,
    ): Relation {
        $model   = $this->getModelInstance($model);
        $through = $this->getModelInstance($through);

        $foreignKey ??= get_foreign_key($through);
        $throughForeignKey ??= get_foreign_key($model);

        return $this->addRelation($model, RelationTypes::hasMany, $foreignKey, $primaryKey)
            ->setThrough($through, $throughForeignKey, $throughPrimaryKey);
    }

    public function belongsToMany(Model|string $model, ?string $pivotTable = null, ?string $pivotForeignKey = null, ?string $pivotRelatedKey = null)
    {
        $model = $this->getModelInstance($model);

        $pivotTable ??= $this->createPivotTableName($this->table, $model->getTable());
        $pivotForeignKey ??= get_foreign_key($this);
        $pivotRelatedKey ??= get_foreign_key($model);

        return $this->addRelation($model, RelationTypes::belongsToMany)
            ->setMany($pivotTable, $pivotForeignKey, $pivotRelatedKey);
    }

    /**
     * Return model instance.
     */
    private function getModelInstance(Model|string $model): Model
    {
        return $model instanceof Model ? $model : model($model);
    }

    /**
     * Create pivot table name.
     */
    private function createPivotTableName(mixed $table1, mixed $table2): string
    {
        $tables = [$table1, $table2];
        sort($tables);

        $tables = array_map('singular', $tables);

        return implode('_', $tables);
    }

    /**
     * Get the caller method name.
     *
     * @throws ReflectionException
     */
    private function getInitialMethodName(): string
    {
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);

        $reflectionClass = new ReflectionClass(self::class);
        $classFile       = $reflectionClass->getFileName();

        foreach ($backtrace as $trace) {
            if (isset($trace['class']) && $trace['class'] === self::class) {
                $method = new ReflectionMethod($trace['class'], $trace['function']);

                // Check if the method is declared in the current class, not a trait
                if ($method->getFileName() !== $classFile) {
                    continue;
                }

                // Check if the return type is a Relation class
                $returnType = $method->getReturnType();
                if ($returnType instanceof ReflectionNamedType && $returnType->getName() === Relation::class) {
                    return $trace['function'];
                }
            }
        }

        throw new LogicException('No initial method with Relation return type found in the current class.');
    }

    /**
     * Reset all relations for model
     */
    private function resetRelations(): void
    {
        $keys                = array_keys($this->relations);
        $this->allowedFields = array_diff($this->allowedFields, $keys);
        $this->relations     = [];
    }

    /**
     * Before insert event.
     */
    protected function relationsBeforeInsert(array $eventData): array
    {
        foreach ($this->relations as $relationName => $relationObject) {
            if (array_key_exists($relationName, $eventData['data'])) {
                $relationObject->setData($eventData['data'][$relationName]);
                unset($eventData['data'][$relationName]);
            }
        }

        return $eventData;
    }

    /**
     * After insert event.
     *
     * @throws ReflectionException
     */
    protected function relationsAfterInsert(array $eventData): array
    {
        if (! $eventData['result'] || $this->relations === []) {
            return $eventData;
        }

        foreach ($this->relations as $relationObject) {
            foreach ($relationObject->getData() as $row) {
                $row    = $this->transformDataToArray($row, 'insert');
                $result = $relationObject->applyWith()->model->insert(array_merge($row, [
                    $relationObject->foreignKey => $eventData[$this->primaryKey],
                ]));

                if ($result === false) {
                    $this->relationErrors = array_merge($this->relationErrors, $relationObject->model->errors());
                    $eventData['result']  = false;

                    return $eventData;
                }
            }
        }

        $this->resetRelations();

        return $eventData;
    }

    /**
     * Before update event.
     */
    protected function relationsBeforeUpdate(array $eventData): array
    {
        foreach ($this->relations as $relationName => $relationObject) {
            if (array_key_exists($relationName, $eventData['data'])) {
                $relationObject->setData($eventData['data'][$relationName]);
                unset($eventData['data'][$relationName]);
            }
        }

        return $eventData;
    }

    /**
     * After update event.
     *
     * @throws ReflectionException
     */
    protected function relationsAfterUpdate(array $eventData): array
    {
        if (! $eventData['result'] || $this->relations === []) {
            return $eventData;
        }

        foreach ($this->relations as $relationObject) {
            foreach ($relationObject->getData() as $row) {
                $row = $this->transformDataToArray($row, 'insert');

                foreach ($eventData[$this->primaryKey] as $id) {
                    $query = $relationObject->applyWith()->model->where($relationObject->foreignKey, $id);

                    $relationObject->applyConditions();

                    $result = $query->save(array_merge($row, [
                        $relationObject->foreignKey => $id,
                    ]));

                    if ($result === false) {
                        $this->relationErrors = array_merge($this->relationErrors, $relationObject->model->errors());
                        $eventData['result']  = false;

                        return $eventData;
                    }
                }
            }
        }

        $this->resetRelations();

        return $eventData;
    }

    /**
     * After find event.
     */
    protected function relationsAfterFind(array $eventData): array
    {
        if (($eventData['data'] || $this->relations === []) === false) {
            return $eventData;
        }

        if ($eventData['singleton']) {
            if ($this->tempReturnType === 'array') {
                foreach ($this->relations as $relationName => $relationObject) {
                    $eventData['data'][$relationName] = $this->getDataForRelationById($eventData['data'][$relationObject->primaryKey], $relationObject);
                }
            } else {
                foreach ($this->relations as $relationName => $relationObject) {
                    $eventData['data']->{$relationName} = $this->getDataForRelationById($eventData['data']->{$relationObject->primaryKey}, $relationObject);
                }
            }
        } else {
            foreach ($this->relations as $relationName => $relationObject) {
                $ids          = array_column($eventData['data'], $relationObject->primaryKey);
                $relationData = $this->getDataForRelationByIds($ids, $relationObject);

                foreach ($eventData['data'] as &$data) {
                    if ($this->tempReturnType === 'array') {
                        $data[$relationName] = $relationData[$data[$relationObject->primaryKey]] ?? [];
                    } else {
                        $data->{$relationName} = $relationData[$data->{$relationObject->primaryKey}] ?? [];
                    }
                }
            }
        }

        $this->resetRelations();

        return $eventData;
    }

    /**
     * Get relation data for a single item.
     */
    protected function getDataForRelationById(int|string $id, Relation $relation)
    {
        $relation->applyWith()->applyRelation([$id], $this->primaryKey)->applyConditions();

        $results = in_array($relation->type, [RelationTypes::hasOne, RelationTypes::belongsTo], true) ?
            $relation->model->first() :
            $relation->model->findAll();

        return $relation->filterResults($results, $this->tempReturnType);
    }

    /**
     * Get relation data for many items.
     */
    protected function getDataForRelationByIds(array $id, Relation $relation): array
    {
        $relation->applyWith()->applyRelation($id, $this->primaryKey)->applyConditions();

        if ($relation->type === RelationTypes::hasOne && ($ofMany = $relation->getOfMany()) !== null) {
            $results = $relation->model
                ->select(sprintf('%s.*', $relation->model->getTable()))
                ->join(
                    sprintf(
                        '%s relation1',
                        $relation->model->getTable(),
                    ),
                    sprintf(
                        '%s.%s = %s.%s AND %s.%s %s %s.%s',
                        $relation->model->getTable(),
                        $relation->foreignKey,
                        'relation1',
                        $relation->foreignKey,
                        $relation->model->getTable(),
                        $ofMany->getField(),
                        $ofMany->getOrder(),
                        'relation1',
                        $ofMany->getField(),
                    ),
                    'LEFT',
                )
                ->where('relation1.' . $relation->primaryKey, null)
                ->findAll();
        } else {
            $results = $relation->model->findAll();
        }

        $relationData = [];
        $key          = $relation->foreignKey;

        if (in_array($relation->type, [RelationTypes::hasOne, RelationTypes::belongsTo], true)) {
            foreach ($results as $row) {
                $relationData[$this->tempReturnType === 'array' ? $row[$key] : $row->{$key}] = $row;
            }
        } else {
            foreach ($results as $row) {
                $arrayKey                  = $this->tempReturnType === 'array' ? $row[$key] : $row->{$key};
                $row                       = $relation->filterResult($row, $this->tempReturnType);
                $relationData[$arrayKey][] = $row;
            }
        }

        return $relationData;
    }

    /**
     * Validate if given relation can be handled during write operation.
     */
    protected function validateWriteRelations(): void
    {
        if ($this->relations === []) {
            return;
        }

        foreach ($this->relations as $relation) {
            if (
                ! in_array($relation->type, [RelationTypes::hasOne, RelationTypes::hasMany], true)
                || (
                    in_array($relation->type, [RelationTypes::hasOne, RelationTypes::hasMany], true)
                    && ($relation->hasMany() || $relation->hasThrough())
                )
            ) {
                throw NestedModelException::forRelationDoesNotSupportWrite();
            }
        }
    }

    /**
     * Whether to use transaction during insert/update.
     */
    public function useTransactions(bool $value = true): static
    {
        $this->useTransactions = $value;

        return $this;
    }

    public function insert($row = null, bool $returnID = true): bool|int|string
    {
        $this->validateWriteRelations();

        if ($this->useTransactions) {
            try {
                $this->db->transException(true)->transStart();

                $result = parent::insert($row, $returnID);

                if ($this->errors() !== []) {
                    $this->db->transRollback();

                    return $result;
                }

                $this->db->transComplete();
            } catch (DatabaseException|DataException $e) {
                $this->relationErrors['database_error'] = $e->getMessage();

                return false;
            } finally {
                $this->useTransactions(false);
            }

            return $result;
        }

        return parent::insert($row, $returnID);
    }

    public function update($id = null, $row = null): bool
    {
        $this->validateWriteRelations();

        if ($this->useTransactions) {
            try {
                $this->db->transException(true)->transStart();

                $result = parent::update($id, $row);

                if ($this->errors() !== []) {
                    $this->db->transRollback();

                    return $result;
                }

                $this->db->transComplete();
            } catch (DatabaseException|DataException $e) {
                $this->relationErrors['database_error'] = $e->getMessage();

                return false;
            } finally {
                $this->useTransactions(false);
            }

            return $result;
        }

        return parent::update($id, $row);
    }

    public function errors(bool $forceDB = false)
    {
        if ($this->relationErrors !== []) {
            return $this->relationErrors;
        }

        return parent::errors($forceDB);
    }
}
