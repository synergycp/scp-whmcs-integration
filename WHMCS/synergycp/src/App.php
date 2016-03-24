<?php

namespace Scp\Whmcs;

use Closure;
use Scp\Api\Api;
use Scp\Support\Arr;
use Scp\Whmcs\Whmcs\Whmcs;

/**
 * Class Responsibilities:
 *  - Direct WHMCS on when and how to use the Scp API.
 *  - Store a static instance of itself.
 */
class App
{
    /**
     * Static instance of the Whmcs class.
     *
     * @var Whmcs
     */
    protected static $selfInstance;

    /**
     * @var array
     */
    protected $singleton = [];

    protected $instance = [];

    public function __construct(array $params = [])
    {
        $this->singleton(Api::class);
        $this->singleton(Whmcs::class, function () use ($params) {
            return new Whmcs($params);
        });
    }

    /**
     * Point $class to a single (not yet computed) instance of $value.
     *
     * @param  string $class
     * @param  Closure|string|null $value
     *
     * @return $this
     */
    public function singleton($class, $value = null)
    {
        $this->singleton[$class] = $value ?: $class;

        return $this;
    }

    /**
     * Point $class to a specific instance of $value.
     *
     * @param  string $class
     * @param  Closure|string|null $value
     *
     * @return $this
     */
    public function instance($class, $value)
    {
        $this->instance[$class] = $value;

        return $this;
    }

    public function make($class)
    {
        return $this->resolve($class);
    }

    public function resolve($class)
    {
        if ($instance = Arr::get($this->instance, $class)) {
            return $instance;
        }

        if ($singleton = Arr::get($this->singleton, $class)) {
            if (!is_a($singleton, Closure::class)) {
                $singleton = function () use ($singleton) {
                    return $this->resolveNew($singleton);
                };
            }

            $instance = $singleton($this);
            $this->instance[$class] = $instance;
            return $instance;
        }

        return $this->resolveNew($class);
    }

    public function resolveNew($class)
    {
        return new $class;
    }

    public static function get(array $params = [])
    {
        return static::$selfInstance = static::$selfInstance
            ?: new static($params);
    }
}
