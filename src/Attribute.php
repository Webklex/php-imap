<?php
/*
* File:     Attribute.php
* Category: -
* Author:   M. Goldenbaum
* Created:  01.01.21 20:17
* Updated:  -
*
* Description:
*  -
*/

namespace Webklex\PHPIMAP;

use ArrayAccess;
use Carbon\Carbon;

/**
 * Class Attribute
 *
 * @package Webklex\PHPIMAP
 *
 * @implements ArrayAccess<TKey, TValue>
 *
 * @template TKey of array-key
 * @template TValue
 */
class Attribute implements ArrayAccess {

    /** @var string $name */
    protected string $name;

    /**
     * Value holder
     *
     * @var array<TKey, TValue> $values
     */
    protected array $values = [];

    /**
     * Attribute constructor.
     * @param string $name
     * @param TValue|TValue[]|null $value
     */
    public function __construct(string $name, mixed $value = null) {
        $this->setName($name);
        $this->add($value);
    }

    /**
     * Handle class invocation calls
     *
     * @return array|string
     */
    public function __invoke(): array|string {
        if ($this->count() > 1) {
            return $this->toArray();
        }
        return $this->toString();
    }

    /**
     * Return the serialized address
     *
     * @return array<TKey, TValue>
     */
    public function __serialize(){
        return $this->values;
    }

    /**
     * Return the stringified attribute
     *
     * @return string
     */
    public function __toString() {
        return implode(", ", $this->values);
    }

    /**
     * Return the stringified attribute
     *
     * @return string
     */
    public function toString(): string {
        return $this->__toString();
    }

    /**
     * Convert instance to array
     *
     * @return array<TKey, TValue>
     */
    public function toArray(): array {
        return $this->__serialize();
    }

    /**
     * Convert first value to a date object
     *
     * @return Carbon
     */
    public function toDate(): Carbon {
        $date = $this->first();
        if ($date instanceof Carbon) return $date;

        return Carbon::parse($date);
    }

    /**
     * Determine if a value exists at a given key.
     *
     * @param TKey $key
     * @return bool
     */
    public function has(mixed $key = 0): bool {
        return array_key_exists($key, $this->values);
    }

    /**
     * Determine if a value exists at a given key.
     *
     * @param TKey $key
     * @return bool
     */
    public function exist(mixed $key = 0): bool {
        return $this->has($key);
    }

    /**
     * Check if the attribute contains the given value
     * @param mixed $value
     *
     * @return bool
     */
    public function contains(mixed $value): bool {
        return in_array($value, $this->values, true);
    }

    /**
     * Get a value by a given key.
     *
     * @param TKey $key
     *
     * @return TValue|null
     */
    public function get(int|string $key = 0) {
        return $this->values[$key] ?? null;
    }

    /**
     * Set the value by a given key.
     *
     * @param TKey $key
     * @param TValue $value
     * @return $this
     */
    public function set(mixed $value, mixed $key = 0): Attribute {
        if (is_null($key)) {
            $this->values[] = $value;
        } else {
            $this->values[$key] = $value;
        }
        return $this;
    }

    /**
     * Unset a value by a given key.
     *
     * @param TKey $key
     * @return $this
     */
    public function remove(int|string $key = 0): Attribute {
        if (isset($this->values[$key])) {
            unset($this->values[$key]);
        }
        return $this;
    }

    /**
     * Add one or more values to the attribute
     * @param TValue|TValue[]|null $value
     * @param boolean $strict
     *
     * @return $this
     */
    public function add(mixed $value, bool $strict = false): Attribute {
        if (is_array($value)) {
            return $this->merge($value, $strict);
        }elseif ($value !== null) {
            $this->attach($value, $strict);
        }

        return $this;
    }

    /**
     * Merge a given array of values with the current values array
     * @param TValue[] $values
     * @param boolean $strict
     *
     * @return $this
     */
    public function merge(array $values, bool $strict = false): Attribute {
        foreach ($values as $value) {
            $this->attach($value, $strict);
        }

        return $this;
    }

    /**
     * Attach a given value to the current value array
     * @param TValue $value
     * @param bool $strict
     * @return $this
     */
    public function attach($value, bool $strict = false): Attribute {
        if ($strict === true) {
            if ($this->contains($value) === false) {
                $this->values[] = $value;
            }
        }else{
            $this->values[] = $value;
        }
        return $this;
    }

    /**
     * Set the attribute name
     * @param $name
     *
     * @return $this
     */
    public function setName($name): Attribute {
        $this->name = $name;

        return $this;
    }

    /**
     * Get the attribute name
     *
     * @return string
     */
    public function getName(): string {
        return $this->name;
    }

    /**
     * Get all values
     *
     * @return array<TKey, TValue>
     */
    public function all(): array {
        reset($this->values);
        return $this->values;
    }

    /**
     * Get the first value if possible
     *
     * @return TValue|null
     */
    public function first(): mixed {
        return reset($this->values);
    }

    /**
     * Get the last value if possible
     *
     * @return TValue|null
     */
    public function last(): mixed {
        return end($this->values);
    }

    /**
     * Get the number of values
     *
     * @return int
     */
    public function count(): int {
        return count($this->values);
    }

    /**
     * @see  ArrayAccess::offsetExists
     * @param TKey $offset
     * @return bool
     */
    public function offsetExists(mixed $offset): bool {
        return $this->has($offset);
    }

    /**
     * @see  ArrayAccess::offsetGet
     * @param TKey $offset
     * @return TValue|null
     */
    public function offsetGet(mixed $offset): mixed {
        return $this->get($offset);
    }

    /**
     * @see  ArrayAccess::offsetSet
     * @param TKey $offset
     * @param TValue $value
     * @return void
     */
    public function offsetSet(mixed $offset, mixed $value): void {
        $this->set($value, $offset);
    }

    /**
     * @see  ArrayAccess::offsetUnset
     * @param TKey $offset
     * @return void
     */
    public function offsetUnset(mixed $offset): void {
        $this->remove($offset);
    }

    /**
     * @template TReturn
     * @param callable(TValue):TReturn $callback
     * @return array<TKey, TReturn>
     */
    public function map(callable $callback): array {
        return array_map($callback, $this->values);
    }
}