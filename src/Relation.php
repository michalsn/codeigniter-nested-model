<?php

declare(strict_types=1);

namespace Michalsn\CodeIgniterNestedModel;

use Closure;
use CodeIgniter\Entity\Entity;
use CodeIgniter\Model;
use Michalsn\CodeIgniterNestedModel\Enums\OrderTypes;
use Michalsn\CodeIgniterNestedModel\Enums\RelationTypes;
use Michalsn\CodeIgniterNestedModel\Exceptions\NestedModelException;
use ReflectionObject;

class Relation
{
    private ?Closure $conditions = null;
    private ?OfMany $ofMany      = null;
    private ?Through $through    = null;
    private ?Many $many          = null;

    /**
     * @var list<With>
     */
    private ?array $with = null;

    /**
     * @var list<mixed>
     */
    private array|Entity $data = [];

    public function __construct(
        public readonly RelationTypes $type,
        public readonly Model $model,
        public readonly string $foreignKey,
        public readonly string $primaryKey,
    ) {
    }

    public function setConditions(Closure $closure): static
    {
        $this->conditions = $closure;

        return $this;
    }

    public function applyConditions(): static
    {
        if ($this->conditions === null) {
            return $this;
        }

        $closure = $this->conditions;
        $closure($this->model);

        $this->conditions = null;

        return $this;
    }

    /**
     * @param list<mixed> $data
     */
    public function setData(array|Entity $data): static
    {
        $this->data = $data;

        return $this;
    }

    /**
     * @return list<mixed>
     */
    public function getData(): array|Entity
    {
        return ($this->type === RelationTypes::hasOne) ?
            [$this->data] :
            $this->data;
    }

    public function setWith(With $with): static
    {
        $this->with[] = $with;

        return $this;
    }

    public function applyWith(): static
    {
        if ($this->with === null) {
            return $this;
        }

        foreach ($this->with as &$item) {
            $this->model->with($item->name, $item->closure);
            unset($item);
        }

        return $this;
    }

    public function setThrough(Model $model, ?string $foreignKey = null, ?string $primaryKey = null): static
    {
        $this->through = new Through(
            $model,
            $foreignKey ?? get_foreign_key($model),
            $primaryKey ?? get_primary_key($model),
        );

        return $this;
    }

    public function hasThrough(): bool
    {
        return $this->through !== null;
    }

    public function setMany(string $pivotTable, string $pivotForeignKey, string $pivotRelatedKey): static
    {
        $this->many = new Many($pivotTable, $pivotForeignKey, $pivotRelatedKey);

        return $this;
    }

    public function hasMany(): bool
    {
        return $this->many !== null;
    }

    public function getMany(): ?Many
    {
        return $this->many;
    }

    public function applyRelation(array $id, string $primaryKey): static
    {
        if ($this->through !== null) {
            $this->model
                ->select(sprintf('%s.*', $this->model->getTable()))
                ->join(
                    $this->through->model->getTable(),
                    sprintf(
                        '%s.%s = %s.%s',
                        $this->through->model->getTable(),
                        $this->through->primaryKey,
                        $this->model->getTable(),
                        $this->foreignKey,
                    ),
                    'LEFT',
                )
                ->whereIn(
                    sprintf(
                        '%s.%s',
                        $this->through->model->getTable(),
                        $primaryKey,
                    ),
                    $id,
                );

            return $this;
        }

        if ($this->many !== null) {
            $this->model
                ->select(
                    sprintf(
                        '%s.*, %s.%s',
                        $this->model->getTable(),
                        $this->many->pivotTable,
                        $this->many->pivotForeignKey,
                    ),
                )
                ->join(
                    $this->many->pivotTable,
                    sprintf(
                        '%s.%s = %s.%s',
                        $this->many->pivotTable,
                        $this->many->pivotRelatedKey,
                        $this->model->getTable(),
                        get_primary_key($this->model),
                    ),
                    'LEFT',
                )
                ->whereIn(
                    sprintf(
                        '%s.%s',
                        $this->many->pivotTable,
                        $this->many->pivotForeignKey,
                    ),
                    $id,
                );

            return $this;
        }

        $this->model->whereIn(
            sprintf(
                '%s.%s',
                $this->model->getTable(),
                $this->foreignKey,
            ),
            $id,
        );

        return $this;
    }

    public function getOfMany(): ?OfMany
    {
        return $this->ofMany;
    }

    public function latestOfMany(): static
    {
        $this->setOrder($this->getOrderField($this->model), OrderTypes::DESC);

        return $this;
    }

    public function oldestOfMany(): static
    {
        $this->setOrder($this->getOrderField($this->model), OrderTypes::ASC);

        return $this;
    }

    public function ofMany(string $field, OrderTypes $order): static
    {
        $this->setOrder($field, $order);

        return $this;
    }

    private function setOrder(string $field, OrderTypes $order): void
    {
        if ($this->type !== RelationTypes::hasOne) {
            throw NestedModelException::forMethodNotSupported($this->type->name);
        }

        $this->model->orderBy($field, $order->value);

        $this->ofMany = new OfMany($field, $order);
    }

    private function getOrderField(Model $model): string
    {
        $refObj = new ReflectionObject($model);

        $refProp       = $refObj->getProperty('useTimestamps');
        $useTimestamps = $refProp->getValue($model);

        if ($useTimestamps) {
            $refProp = $refObj->getProperty('createdField');

            return $refProp->getValue($model);
        }

        $refProp = $refObj->getProperty('primaryKey');

        return $refProp->getValue($model);
    }

    public function filterResult(array|Entity $row, string $returnType): array|Entity
    {
        if ($row === [] || $this->type !== RelationTypes::belongsToMany) {
            return $row;
        }

        if ($returnType === 'array') {
            unset($row[$this->many->pivotForeignKey]);
        } else {
            unset($row->{$this->many->pivotForeignKey});
        }

        return $row;
    }

    public function filterResults(array|Entity $results, string $returnType): array|Entity
    {
        if ($this->type !== RelationTypes::belongsToMany) {
            return $results;
        }

        foreach ($results as &$row) {
            if ($returnType === 'array') {
                unset($row[$this->many->pivotForeignKey]);
            } else {
                unset($row->{$this->many->pivotForeignKey});
            }
        }

        return $results;
    }
}
