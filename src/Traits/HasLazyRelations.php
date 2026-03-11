<?php

declare(strict_types=1);

namespace Michalsn\CodeIgniterNestedModel\Traits;

use CodeIgniter\Autoloader\FileLocatorInterface;
use CodeIgniter\Model;
use Michalsn\CodeIgniterNestedModel\Enums\RelationTypes;

trait HasLazyRelations
{
    /**
     * @var array<string, bool>
     */
    private array $handledRelations = [];

    private ?Model $relationModel       = null;
    private bool $relationModelResolved = false;

    public function __get(string $key)
    {
        if (array_key_exists($key, $this->attributes)) {
            return parent::__get($key);
        }

        $model = $this->getRelationModel();

        if ($model !== null && method_exists($model, $key)) {
            if (! isset($this->handledRelations[$key]) && ! array_key_exists($key, $this->attributes)) {
                $this->handleRelation($key, $model);
                $this->handledRelations[$key] = true;
            }

            return $this->attributes[$key] ?? null;
        }

        return parent::__get($key);
    }

    /**
     * Load relation for the property.
     */
    private function handleRelation(string $name, Model $model)
    {
        $relation = $model->{$name}();

        if (in_array($relation->type, [RelationTypes::hasOne, RelationTypes::belongsTo], true)) {
            $this->attributes[$name] = $relation->filterResult(
                $relation
                    ->applyRelation([$this->attributes[$relation->primaryKey]], $relation->foreignKey)
                    ->model
                    ->first(),
                'object',
            );
        } else {
            $this->attributes[$name] = $relation->filterResults(
                $relation->applyRelation([$this->attributes[$relation->primaryKey]], $relation->foreignKey)
                    ->model
                    ->findAll(),
                'object',
            );
        }

        return $this->attributes[$name];
    }

    /**
     * Resolve the matching model once for the lifetime of the entity instance.
     */
    private function getRelationModel(): ?Model
    {
        if ($this->relationModelResolved) {
            return $this->relationModel;
        }

        $className = $this->findModelClass();

        $this->relationModel         = $className === null ? null : model($className);
        $this->relationModelResolved = true;

        return $this->relationModel;
    }

    /**
     * Search for the proper model.
     *
     * First search in the same folder in the models.
     */
    private function findModelClass(): ?string
    {
        /** @var FileLocatorInterface $locator */
        $locator = service('locator');

        $className     = static::class;
        $namespace     = substr($className, strpos($className, 'Entities'));
        $baseNamespace = substr($namespace, 0, strrpos($namespace, '\\'));
        $baseClassName = substr(strrchr($className, '\\'), 1);
        $modelPath     = str_replace('Entities', 'Models', $baseNamespace) . '\\' . $baseClassName . 'Model';

        $files = $locator->search(str_replace('\\', '/', $modelPath) . '.php');

        if ($files === []) {
            // Fallback to search in the base Models directory
            $modelPath = 'Models\\' . $baseClassName . 'Model';
            $files     = $locator->search(str_replace('\\', '/', $modelPath) . '.php');
        }

        if ($files === []) {
            return null;
        }

        return $locator->findQualifiedNameFromPath($files[0]);
    }
}
