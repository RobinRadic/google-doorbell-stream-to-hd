<?php

namespace App\Support\Concerns;

use Illuminate\Support\Arr;

/**
 * @see \App\Support\Attributes
 */
trait AttributesOffsetTrait
{
    abstract protected function getAttributesPropertyName();

    public function get(string $path, $default = null)
    {
        return Arr::get($this->{$this->getAttributesPropertyName()}, $path, $default);
    }

    public function set(string $path, $value)
    {
        Arr::set($this->{$this->getAttributesPropertyName()}, $path, $value);
        return $this;
    }

    public function has(string $path)
    {
        return Arr::has($this->{$this->getAttributesPropertyName()}, $path);
    }

    public function unset(string|array $paths)
    {
        Arr::forget($this->{$this->getAttributesPropertyName()}, Arr::wrap($paths));
        return $this;
    }

    public function add($key, $value)
    {
        $this->{$this->getAttributesPropertyName()} = Arr::add($this->{$this->getAttributesPropertyName()}, $key, $value);
        return $this;
    }

    public function merge(array $attributes, string $path = null, $unique = true)
    {
        if ($path !== null) {
            $this->set($path, Arr::merge($this->get($path, []), $attributes, $unique));
            return $this;
        }
        $this->{$this->getAttributesPropertyName()} = Arr::merge($this->{$this->getAttributesPropertyName()}, $attributes, $unique);
        return $this;
    }

    public function getAttributes()
    {
        return $this->{$this->getAttributesPropertyName()};
    }

    public function setAttributes(array $attributes)
    {
        $this->{$this->getAttributesPropertyName()} = $attributes;
        return $this;
    }

    /**
     * Whether a offset exists
     * @link https://php.net/manual/en/arrayaccess.offsetexists.php
     * @param mixed $offset <p>
     *                      An offset to check for.
     *                      </p>
     * @return bool true on success or false on failure.
     *                      </p>
     *                      <p>
     *                      The return value will be casted to boolean if non-boolean was returned.
     */
    public function offsetExists($offset)
    {
        return $this->has($offset);
    }

    /**
     * Offset to retrieve
     * @link https://php.net/manual/en/arrayaccess.offsetget.php
     * @param mixed $offset <p>
     *                      The offset to retrieve.
     *                      </p>
     * @return mixed Can return all value types.
     */
    public function offsetGet($offset)
    {
        return $this->get($offset);
    }

    /**
     * Offset to set
     * @link https://php.net/manual/en/arrayaccess.offsetset.php
     * @param mixed $offset <p>
     *                      The offset to assign the value to.
     *                      </p>
     * @param mixed $value  <p>
     *                      The value to set.
     *                      </p>
     * @return void
     */
    public function offsetSet($offset, $value)
    {
        $this->set($offset, $value);
    }

    /**
     * Offset to unset
     * @link https://php.net/manual/en/arrayaccess.offsetunset.php
     * @param mixed $offset <p>
     *                      The offset to unset.
     *                      </p>
     * @return void
     */
    public function offsetUnset($offset)
    {
        Arr::forget($this->{$this->getAttributesPropertyName()}, $offset);
    }
}
