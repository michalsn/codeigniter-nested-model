<?php

declare(strict_types=1);

namespace Michalsn\CodeIgniterNestedModel;

use Michalsn\CodeIgniterNestedModel\Enums\OrderTypes;

readonly class OfMany
{
    public function __construct(private string $field, private OrderTypes $order)
    {
    }

    public function getField(): string
    {
        return $this->field;
    }

    public function getOrder(): string
    {
        return $this->order->sign();
    }
}
