<?php

declare(strict_types=1);

namespace Tests\Support\Entities;

use CodeIgniter\Entity\Entity;
use LogicException;

abstract class StrictEntity extends Entity
{
    protected $dates = [];

    public function __construct(?array $data = null)
    {
        parent::__construct(is_array($data) ? $this->filterAttributes($data) : []);
    }

    public function fill(?array $data = null)
    {
        return parent::fill(is_array($data) ? $this->filterAttributes($data) : []);
    }

    public function injectRawData(array $data)
    {
        return parent::injectRawData(array_merge($this->attributes, $this->filterAttributes($data)));
    }

    public function __set(string $key, $value = null)
    {
        $attribute = $this->mapProperty($key);

        if (! array_key_exists($attribute, $this->attributes)) {
            throw new LogicException(sprintf('Attribute "%s" is not defined.', $attribute));
        }

        parent::__set($key, $value);
    }

    public function __get(string $key)
    {
        $attribute = $this->mapProperty($key);

        if (! array_key_exists($attribute, $this->attributes)) {
            throw new LogicException(sprintf('Attribute "%s" is not defined.', $attribute));
        }

        return parent::__get($key);
    }

    /**
     * @param array<string, mixed> $attributes
     *
     * @return array<string, mixed>
     */
    protected function filterAttributes(array $attributes): array
    {
        return array_intersect_key($attributes, $this->attributes);
    }
}
