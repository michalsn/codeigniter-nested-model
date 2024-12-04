<?php

declare(strict_types=1);

namespace Michalsn\CodeIgniterNestedModel\Traits;

use CodeIgniter\Autoloader\FileLocatorInterface;
use Michalsn\CodeIgniterNestedModel\Enums\RelationTypes;

trait HasLazyRelations
{
    public function __get(string $key)
    {
        $result = parent::__get($key);

        if ($result === null) {
            $result = $this->handleRelation($key);
        }

        return $result;
    }

    /**
     * Load relation for the property.
     */
    private function handleRelation(string $name)
    {
        $className = $this->findModelClass();

        if ($className === null) {
            return null;
        }

        $model = model($className);

        if (! method_exists($model, $name)) {
            return null;
        }

        $relation = $model->{$name}();

        $relation->model->where($relation->foreignKey, $this->attributes[$relation->primaryKey]);

        if (in_array($relation->type, [RelationTypes::hasOne, RelationTypes::belongTo], true)) {
            $this->attributes[$name] = $relation->model->first();
        } else {
            $this->attributes[$name] = $relation->model->findAll();
        }

        return $this->attributes[$name];
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
