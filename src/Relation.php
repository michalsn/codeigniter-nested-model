<?php

declare(strict_types=1);

namespace Michalsn\CodeIgniterNestedModel;

use BadMethodCallException;
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
        public readonly string $primaryKey
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

    public function getOfMany(): OfMany
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
}
