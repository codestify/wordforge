<?php

namespace WordForge\Support\Facades;

/**
 * Collection Facade
 *
 * @method static \WordForge\Support\Collection make(mixed $items = [])
 * @method static array all()
 * @method static \WordForge\Support\Collection map(callable $callback)
 * @method static \WordForge\Support\Collection filter(callable $callback = null)
 * @method static \WordForge\Support\Collection reject(callable $callback)
 * @method static mixed reduce(callable $callback, mixed $initial = null)
 * @method static \WordForge\Support\Collection pluck(string|array $value, string|null $key = null)
 * @method static \WordForge\Support\Collection values()
 * @method static \WordForge\Support\Collection keys()
 * @method static bool has(mixed $key)
 * @method static mixed get(mixed $key, mixed $default = null)
 * @method static mixed first(callable|null $callback = null, mixed $default = null)
 * @method static mixed last(callable|null $callback = null, mixed $default = null)
 * @method static \WordForge\Support\Collection each(callable $callback)
 * @method static \WordForge\Support\Collection chunk(int $size)
 * @method static mixed avg(callable|string|null $callback = null)
 * @method static mixed average(callable|string|null $callback = null)
 * @method static mixed sum(callable|string|null $callback = null)
 * @method static mixed max(callable|string|null $callback = null)
 * @method static mixed min(callable|string|null $callback = null)
 * @method static \WordForge\Support\Collection sort(callable|string|null $callback = null, int $options = SORT_REGULAR, bool $descending = false)
 * @method static \WordForge\Support\Collection sortDesc(callable|string|null $callback = null, int $options = SORT_REGULAR)
 * @method static \WordForge\Support\Collection sortKeys(int $options = SORT_REGULAR, bool $descending = false)
 * @method static \WordForge\Support\Collection sortKeysDesc(int $options = SORT_REGULAR)
 * @method static \WordForge\Support\Collection reverse()
 * @method static mixed random(int|null $number = null)
 * @method static \WordForge\Support\Collection shuffle()
 * @method static \WordForge\Support\Collection slice(int $offset, int|null $length = null)
 * @method static \WordForge\Support\Collection take(int $limit)
 * @method static \WordForge\Support\Collection groupBy(callable|string $groupBy)
 * @method static \WordForge\Support\Collection merge(mixed $items)
 * @method static \WordForge\Support\Collection union(mixed $items)
 * @method static array toArray()
 * @method static string toJson(int $options = 0)
 * @method static int count()
 *
 * @package WordForge\Support\Facades
 */
class Collection extends Facade
{
    /**
     * Get the facade accessor.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return \WordForge\Support\Collection::class;
    }

    /**
     * Create a facade instance.
     *
     * @param  string  $accessor
     *
     * @return object
     */
    protected static function createFacadeInstance(string $accessor)
    {
        // Create a new Collection instance
        return new \WordForge\Support\Collection();
    }
}
