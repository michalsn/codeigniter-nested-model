<?php

declare(strict_types=1);

namespace Michalsn\CodeIgniterNestedModel\Traits;

use Closure;
use CodeIgniter\Model;
use InvalidArgumentException;
use LogicException;
use Michalsn\CodeIgniterNestedModel\Enums\RelationTypes;
use Michalsn\CodeIgniterNestedModel\Exceptions\NestedModelException;
use Michalsn\CodeIgniterNestedModel\Relation;
use Michalsn\CodeIgniterNestedModel\With;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionObject;
use UnexpectedValueException;

trait HasRelations
{
    private array $relations = [];

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

        if (! $model instanceof Model) {
            $model = model($model);
        }

        $this->relations[$relation] = new Relation(
            $relationType,
            $model,
            $foreignKey ?? ($relationType === RelationTypes::belongsTo ? $this->relationsGetBelongForeignKey($model) : $this->relationsGetHasForeignKey()),
            $primaryKey ?? ($relationType === RelationTypes::belongsTo ? $this->relationsGetBelongPrimaryKey($model) : $this->relationsGetHasPrimaryKey())
        );

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

    /*
        protected function hasManyThrough(Model|string $model, Model|string $through, ?string $foreignKey = null, ?string $primaryKey = null, ?string $throughForeignKey = null, ?string $throughPrimaryKey = null): void
        {
            $relation = $this->relation ?? $this->getInitialMethodName();

            $this->allowedFields[] = $relation;

            if (! $model instanceof Model) {
                $model = model($model);
            }

            if (! $through instanceof Model) {
                $through = model($through);
            }

            $this->relations[$relation] = new Relation(
                RelationTypes::hasMany,
                $model,
                $foreignKey ?? $this->relationsGetHasForeignKey($model),
                $primaryKey ?? $this->relationsGetHasPrimaryKey()
            );

            $this->relations[$relation]->setThrough($through, $throughForeignKey, $throughPrimaryKey);
        }

        protected function belongsToMany(Model|string $model, Model|string $through, ?string $foreignKey = null, ?string $primaryKey = null, ?string $throughForeignKey = null, ?string $throughPrimaryKey = null): void
        {
            $relation = $this->relation ?? $this->getInitialMethodName();

            $this->allowedFields[] = $relation;

            if (! $model instanceof Model) {
                $model = model($model);
            }

            if (! $through instanceof Model) {
                $through = model($through);
            }

            $this->relations[$relation] = new Relation(
                RelationTypes::hasMany,
                $model,
                $foreignKey ?? $this->relationsGetHasForeignKey($model),
                $primaryKey ?? $this->relationsGetHasPrimaryKey()
            );

            $this->relations[$relation]->setThrough($through, $throughForeignKey, $throughPrimaryKey);
        }
    */

    /**
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

    private function relationsGetHasPrimaryKey(): string
    {
        return $this->primaryKey;
    }

    private function relationsGetHasForeignKey(): string
    {
        return singular($this->table) . '_' . $this->primaryKey;
    }

    /**
     * @throws ReflectionException
     */
    private function relationsGetBelongPrimaryKey(Model $model): string
    {
        $refObj = new ReflectionObject($model);

        $refProp = $refObj->getProperty('table');
        $table   = $refProp->getValue($model);

        $refProp    = $refObj->getProperty('primaryKey');
        $primaryKey = $refProp->getValue($model);

        return singular($table) . '_' . $primaryKey;
    }

    /**
     * @throws ReflectionException
     */
    private function relationsGetBelongForeignKey(Model $model): string
    {
        $refObj = new ReflectionObject($model);

        $refProp = $refObj->getProperty('primaryKey');

        return $refProp->getValue($model);
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
                $row = $this->transformDataToArray($row, 'insert');
                $relationObject->applyWith()->model->insert(array_merge($row, [
                    $relationObject->foreignKey => $eventData[$this->primaryKey],
                ]));
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

                    $query->save(array_merge($row, [
                        $relationObject->foreignKey => $id,
                    ]));
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

    protected function getDataForRelationById(int|string $id, Relation $relation)
    {
        $query = $relation->applyWith()->model->where($relation->foreignKey, $id);

        $relation->applyConditions();

        return in_array($relation->type, [RelationTypes::hasOne, RelationTypes::belongsTo], true) ?
            $query->first() :
            $query->findAll();
    }

    protected function getDataForRelationByIds(array $id, Relation $relation): array
    {
        $query = $relation->applyWith()->model->whereIn(
            sprintf('%s.%s', $relation->model->getTable(), $relation->foreignKey),
            $id
        );

        $relation->applyConditions();

        if ($relation->type === RelationTypes::hasOne && ($ofMany = $relation->getOfMany()) !== null) {
            $results = $query
                ->select(sprintf('%s.*', $relation->model->getTable()))
                ->join(
                    sprintf(
                        '%s relation1',
                        $relation->model->getTable()
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
                    'LEFT'
                )
                ->where('relation1.' . $relation->primaryKey, null)
                ->findAll();
        } else {
            $results = $query->findAll();
        }

        $relationData = [];

        if (in_array($relation->type, [RelationTypes::hasOne, RelationTypes::belongsTo], true)) {
            foreach ($results as $row) {
                $relationData[$this->tempReturnType === 'array' ? $row[$relation->foreignKey] : $row->{$relation->foreignKey}] = $row;
            }
        } else {
            foreach ($results as $row) {
                $relationData[$this->tempReturnType === 'array' ? $row[$relation->foreignKey] : $row->{$relation->foreignKey}][] = $row;
            }
        }

        return $relationData;
    }
}
