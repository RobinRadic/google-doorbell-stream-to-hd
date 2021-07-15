<?php

namespace App\Support;

use ArrayAccess;
use App\Support\Concerns\AttributesOffsetTrait;
use Illuminate\Contracts\Support\Arrayable;

class Attributes implements ArrayAccess, Arrayable
{
    use AttributesOffsetTrait;

    public function __construct(protected array $attributes = [])
    {
    }

    protected function getAttributesPropertyName()
    {
        return 'attributes';
    }

    public function toArray()
    {
        return $this->attributes;
    }
}
