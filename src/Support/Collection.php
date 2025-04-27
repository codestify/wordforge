<?php

namespace WordForge\Support;

use ArrayAccess;
use ArrayIterator;
use Countable;
use IteratorAggregate;
use JsonSerializable;
use Traversable;

/**
 * Collection class for fluent array manipulation
 *
 * Provides a Laravel-inspired collection implementation for WordPress.
 *
 * @package WordForge\Support
 */
class Collection implements ArrayAccess, Countable, IteratorAggregate, JsonSerializable
{
    /**
     * The items contained in the collection.
     *
     * @var array
     */
    protected $items = [];

    /**
     * Create a new collection.
     *
     * @param mixed $items
     * @return void
     */
    public function __construct($items = [])
    {
        $this->items = $this->getArrayableItems($items);
    }

    /**
     * Create a new collection instance.
     *
     * @param mixed $items
     * @return static
     */
    public static function make($items = [])
    {
        return new static($items);
    }

    /**
     * Get all of the items in the collection.
     *
     * @return array
     */
    public function all()
    {
        return $this->items;
    }

    /**
     * Run a map over each of the items.
     *
     * @param callable $callback
     * @return static
     */
    public function map(callable $callback)
    {
        $keys = array_keys($this->items);
        $items = array_map($callback, $this->items, $keys);

        return new static(array_combine($keys, $items));
    }

    /**
     * Run a filter over each of the items.
     *
     * @param callable|null $callback
     * @return static
     */
    public function filter(callable $callback = null)
    {
        if ($callback) {
            return new static(array_filter($this->items, $callback, ARRAY_FILTER_USE_BOTH));
        }

        return new static(array_filter($this->items));
    }

    /**
     * Reject items using the given callback.
     *
     * @param callable $callback
     * @return static
     */
    public function reject(callable $callback)
    {
        return $this->filter(function ($item, $key) use ($callback) {
            return !$callback($item, $key);
        });
    }

    /**
     * Reduce the collection to a single value.
     *
     * @param callable $callback
     * @param mixed $initial
     * @return mixed
     */
    public function reduce(callable $callback, $initial = null)
    {
        return array_reduce($this->items, $callback, $initial);
    }

    /**
     * Pluck an array of values from an array.
     *
     * @param string|array $value
     * @param string|null $key
     * @return static
     */
    public function pluck($value, $key = null)
    {
        $results = [];

        foreach ($this->items as $item) {
            $itemValue = is_object($item) ?
                (isset($item->$value) ? $item->$value : null) :
                (is_array($item) ? ($item[$value] ?? null) : null);

            // If key was specified, use it for the array key
            if ($key !== null) {
                $itemKey = is_object($item) ?
                    (isset($item->$key) ? $item->$key : null) :
                    (is_array($item) ? ($item[$key] ?? null) : null);

                if ($itemKey !== null) {
                    $results[$itemKey] = $itemValue;
                    continue;
                }
            }

            $results[] = $itemValue;
        }

        return new static($results);
    }

    /**
     * Get the values of a given key.
     *
     * @return static
     */
    public function values()
    {
        return new static(array_values($this->items));
    }

    /**
     * Get the keys of the collection items.
     *
     * @return static
     */
    public function keys()
    {
        return new static(array_keys($this->items));
    }

    /**
     * Determine if an item exists in the collection by key.
     *
     * @param mixed $key
     * @return bool
     */
    public function has($key)
    {
        if (is_array($key)) {
            foreach ($key as $k) {
                if (!array_key_exists($k, $this->items)) {
                    return false;
                }
            }
            return true;
        }

        return array_key_exists($key, $this->items);
    }

    /**
     * Get an item from the collection by key.
     *
     * @param mixed $key
     * @param mixed $default
     * @return mixed
     */
    public function get($key, $default = null)
    {
        if (array_key_exists($key, $this->items)) {
            return $this->items[$key];
        }

        return $default instanceof \Closure ? $default() : $default;
    }

    /**
     * Get the first item from the collection.
     *
     * @param callable|null $callback
     * @param mixed $default
     * @return mixed
     */
    public function first(callable $callback = null, $default = null)
    {
        if (is_null($callback)) {
            if (empty($this->items)) {
                return $default instanceof \Closure ? $default() : $default;
            }

            foreach ($this->items as $item) {
                return $item;
            }
        }

        foreach ($this->items as $key => $value) {
            if ($callback($value, $key)) {
                return $value;
            }
        }

        return $default instanceof \Closure ? $default() : $default;
    }

    /**
     * Get the last item from the collection.
     *
     * @param callable|null $callback
     * @param mixed $default
     * @return mixed
     */
    public function last(callable $callback = null, $default = null)
    {
        if (is_null($callback)) {
            return empty($this->items) ?
                ($default instanceof \Closure ? $default() : $default) :
                end($this->items);
        }

        return $this->filter($callback)->last(null, $default);
    }

    /**
     * Execute a callback over each item.
     *
     * @param callable $callback
     * @return $this
     */
    public function each(callable $callback)
    {
        foreach ($this->items as $key => $item) {
            if ($callback($item, $key) === false) {
                break;
            }
        }

        return $this;
    }

    /**
     * Chunk the collection into chunks of the given size.
     *
     * @param int $size
     * @return static
     */
    public function chunk($size)
    {
        if ($size <= 0) {
            return new static();
        }

        $chunks = [];
        foreach (array_chunk($this->items, $size, true) as $chunk) {
            $chunks[] = new static($chunk);
        }

        return new static($chunks);
    }

    /**
     * Get the average value of a given key.
     *
     * @param callable|string|null $callback
     * @return mixed
     */
    public function avg($callback = null)
    {
        $count = $this->count();

        if ($count === 0) {
            return null;
        }

        if ($callback === null) {
            return $this->sum() / $count;
        }

        if (is_callable($callback)) {
            return $this->sum($callback) / $count;
        }

        return $this->pluck($callback)->avg();
    }

    /**
     * Alias for the "avg" method.
     *
     * @param callable|string|null $callback
     * @return mixed
     */
    public function average($callback = null)
    {
        return $this->avg($callback);
    }

    /**
     * Get the sum of the given values.
     *
     * @param callable|string|null $callback
     * @return mixed
     */
    public function sum($callback = null)
    {
        if (is_null($callback)) {
            return array_sum($this->items);
        }

        if (is_string($callback)) {
            return $this->pluck($callback)->sum();
        }

        return $this->reduce(function ($result, $item) use ($callback) {
            return $result + $callback($item);
        }, 0);
    }

    /**
     * Get the max value of a given key.
     *
     * @param callable|string|null $callback
     * @return mixed
     */
    public function max($callback = null)
    {
        $callback = $this->valueRetriever($callback);

        return $this->filter(function ($value) {
            return !is_null($value);
        })->reduce(function ($result, $item) use ($callback) {
            $value = $callback($item);

            return is_null($result) || $value > $result ? $value : $result;
        });
    }

    /**
     * Get the min value of a given key.
     *
     * @param callable|string|null $callback
     * @return mixed
     */
    public function min($callback = null)
    {
        $callback = $this->valueRetriever($callback);

        return $this->filter(function ($value) {
            return !is_null($value);
        })->reduce(function ($result, $item) use ($callback) {
            $value = $callback($item);

            return is_null($result) || $value < $result ? $value : $result;
        });
    }

    /**
     * Sort the collection by the given callback.
     *
     * @param callable|string|null $callback
     * @param int $options
     * @param bool $descending
     * @return static
     */
    public function sort($callback = null, $options = SORT_REGULAR, $descending = false)
    {
        $items = $this->items;

        if (is_null($callback)) {
            $descending ? arsort($items, $options) : asort($items, $options);
        } else {
            $results = [];

            foreach ($items as $key => $value) {
                $results[$key] = $callback($value);
            }

            $descending ? arsort($results, $options) : asort($results, $options);

            foreach (array_keys($results) as $key) {
                $results[$key] = $items[$key];
            }

            $items = $results;
        }

        return new static($items);
    }

    /**
     * Sort the collection in descending order.
     *
     * @param callable|string|null $callback
     * @param int $options
     * @return static
     */
    public function sortDesc($callback = null, $options = SORT_REGULAR)
    {
        return $this->sort($callback, $options, true);
    }

    /**
     * Sort the collection keys.
     *
     * @param int $options
     * @param bool $descending
     * @return static
     */
    public function sortKeys($options = SORT_REGULAR, $descending = false)
    {
        $items = $this->items;

        $descending ? krsort($items, $options) : ksort($items, $options);

        return new static($items);
    }

    /**
     * Sort the collection keys in descending order.
     *
     * @param int $options
     * @return static
     */
    public function sortKeysDesc($options = SORT_REGULAR)
    {
        return $this->sortKeys($options, true);
    }

    /**
     * Reverse items order.
     *
     * @return static
     */
    public function reverse()
    {
        return new static(array_reverse($this->items, true));
    }

    /**
     * Get one or a specified number of items randomly from the collection.
     *
     * @param int|null $number
     * @return static|mixed
     */
    public function random($number = null)
    {
        if (is_null($number)) {
            return $this->items[array_rand($this->items)];
        }

        $results = new static();

        $count = $this->count();

        // If we're asking for more items than we have, just return a shuffle of all items
        if ($number >= $count) {
            return $this->shuffle();
        }

        $keys = array_rand($this->items, $number);

        if (!is_array($keys)) {
            $keys = [$keys];
        }

        foreach ($keys as $key) {
            $results[$key] = $this->items[$key];
        }

        return $results;
    }

    /**
     * Shuffle the items in the collection.
     *
     * @return static
     */
    public function shuffle()
    {
        $items = $this->items;

        shuffle($items);

        return new static($items);
    }

    /**
     * Slice the underlying collection array.
     *
     * @param int $offset
     * @param int|null $length
     * @return static
     */
    public function slice($offset, $length = null)
    {
        return new static(array_slice($this->items, $offset, $length, true));
    }

    /**
     * Take the first or last {$limit} items.
     *
     * @param int $limit
     * @return static
     */
    public function take($limit)
    {
        if ($limit < 0) {
            return $this->slice($limit, abs($limit));
        }

        return $this->slice(0, $limit);
    }

    /**
     * Group an array of items by a given key.
     *
     * @param callable|string $groupBy
     * @return static
     */
    public function groupBy($groupBy)
    {
        $results = [];
        $groupBy = $this->valueRetriever($groupBy);

        foreach ($this->items as $key => $value) {
            $groupKey = $groupBy($value, $key);

            if (!isset($results[$groupKey])) {
                $results[$groupKey] = new static();
            }

            $results[$groupKey][$key] = $value;
        }

        return new static($results);
    }

    /**
     * Merge the collection with the given items.
     *
     * @param mixed $items
     * @return static
     */
    public function merge($items)
    {
        return new static(array_merge($this->items, $this->getArrayableItems($items)));
    }

    /**
     * Union the collection with the given items.
     *
     * @param mixed $items
     * @return static
     */
    public function union($items)
    {
        return new static($this->items + $this->getArrayableItems($items));
    }

    /**
     * Get the collection of items as a plain array.
     *
     * @return array
     */
    public function toArray()
    {
        return array_map(function ($value) {
            if ($value instanceof Arrayable) {
                return $value->toArray();
            } elseif (is_object($value) && method_exists($value, 'toArray')) {
                return $value->toArray();
            }

            return $value;
        }, $this->items);
    }

    /**
     * Get the collection of items as JSON.
     *
     * @param int $options
     * @return string
     */
    public function toJson($options = 0)
    {
        return json_encode($this->jsonSerialize(), $options);
    }

    /**
     * Convert the object into something JSON serializable.
     *
     * @return array
     */
    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
        return array_map(function ($value) {
            if ($value instanceof JsonSerializable) {
                return $value->jsonSerialize();
            } elseif ($value instanceof Arrayable) {
                return $value->toArray();
            } elseif (is_object($value) && method_exists($value, 'toArray')) {
                return $value->toArray();
            }

            return $value;
        }, $this->items);
    }

    /**
     * Get an iterator for the items.
     *
     * @return ArrayIterator
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->items);
    }

    /**
     * Count the number of items in the collection.
     *
     * @return int
     */
    public function count(): int
    {
        return count($this->items);
    }

    /**
     * Determine if an item exists at an offset.
     *
     * @param mixed $key
     * @return bool
     */
    public function offsetExists($key): bool
    {
        return isset($this->items[$key]);
    }

    /**
     * Get an item at a given offset.
     *
     * @param mixed $key
     * @return mixed
     */
    #[\ReturnTypeWillChange]
    public function offsetGet($key)
    {
        return $this->items[$key];
    }

    /**
     * Set the item at a given offset.
     *
     * @param mixed $key
     * @param mixed $value
     * @return void
     */
    public function offsetSet($key, $value): void
    {
        if (is_null($key)) {
            $this->items[] = $value;
        } else {
            $this->items[$key] = $value;
        }
    }

    /**
     * Unset the item at a given offset.
     *
     * @param mixed $key
     * @return void
     */
    public function offsetUnset($key): void
    {
        unset($this->items[$key]);
    }

    /**
     * Get a value retriever callback.
     *
     * @param callable|string|null $value
     * @return callable
     */
    protected function valueRetriever($value)
    {
        if (is_null($value)) {
            return function ($item) {
                return $item;
            };
        }

        if (is_string($value)) {
            return function ($item) use ($value) {
                return is_array($item) ? ($item[$value] ?? null) :
                    (is_object($item) ? ($item->$value ?? null) : null);
            };
        }

        return $value;
    }

    /**
     * Results array of items from Collection or Arrayable.
     *
     * @param mixed $items
     * @return array
     */
    protected function getArrayableItems($items)
    {
        if (is_array($items)) {
            return $items;
        }

        if ($items instanceof self) {
            return $items->all();
        }

        if ($items instanceof Arrayable) {
            return $items->toArray();
        }

        if ($items instanceof JsonSerializable) {
            return $items->jsonSerialize();
        }

        if ($items instanceof Traversable) {
            return iterator_to_array($items);
        }

        return (array) $items;
    }

    /**
     * Add a method to the collection.
     *
     * @param string $name
     * @param callable $callback
     * @return void
     */
    public static function macro($name, callable $callback)
    {
        static::$macros[$name] = $callback;
    }

    /**
     * Dynamically access collection proxies.
     *
     * @param string $key
     * @return mixed
     *
     * @throws \Exception
     */
    public function __get($key)
    {
        if (isset($this->items[$key])) {
            return $this->items[$key];
        }

        throw new \Exception("Property [{$key}] does not exist on this collection instance.");
    }

    /**
     * Set an item to the collection.
     *
     * @param mixed $key
     * @param mixed $value
     * @return void
     */
    public function __set($key, $value)
    {
        $this->offsetSet($key, $value);
    }

    /**
     * Remove an item from the collection.
     *
     * @param mixed $key
     * @return void
     */
    public function __unset($key)
    {
        $this->offsetUnset($key);
    }

    /**
     * Convert the collection to its string representation.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->toJson();
    }
}
