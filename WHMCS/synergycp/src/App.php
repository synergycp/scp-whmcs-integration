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

    /**
     * @var array
     */
    protected $buildStack = [];

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
     * @param string              $class
     * @param Closure|string|null $value
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
     * @param string              $class
     * @param Closure|string|null $value
     *
     * @return $this
     */
    public function instance($class, $value)
    {
        $this->instance[$class] = $value;

        return $this;
    }

    public function make($class, array $parameters = [])
    {
        return $this->resolve($class, $parameters);
    }

    public function resolve($class, array $parameters = [])
    {
        if ($class == \Scp\Whmcs\Server\Provision\ServerProvisioner::class) {
            return $this->build($class, $parameters);
        }

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

    /**
     * Instantiate a concrete instance of the given type.
     *
     * @param string $concrete
     * @param array  $parameters
     *
     * @return mixed
     *
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function build($concrete, array $parameters = [])
    {
        // If the concrete type is actually a Closure, we will just execute it and
        // hand back the results of the functions, which allows functions to be
        // used as resolvers for more fine-tuned resolution of these objects.
        if ($concrete instanceof \Closure) {
            return $concrete($this, $parameters);
        }

        $reflector = new \ReflectionClass($concrete);

        // If the type is not instantiable, the developer is attempting to resolve
        // an abstract type such as an Interface of Abstract Class and there is
        // no binding registered for the abstractions so we need to bail out.
        if (!$reflector->isInstantiable()) {
            if (!empty($this->buildStack)) {
                $previous = implode(', ', $this->buildStack);
                $message = "Target [$concrete] is not instantiable while building [$previous].";
            } else {
                $message = "Target [$concrete] is not instantiable.";
            }
            throw new \InvalidArgumentException($message);
        }

        $this->buildStack[] = $concrete;
        $constructor = $reflector->getConstructor();

        // If there are no constructors, that means there are no dependencies then
        // we can just resolve the instances of the objects right away, without
        // resolving any other types or dependencies out of these containers.
        if (is_null($constructor)) {
            array_pop($this->buildStack);

            return new $concrete();
        }

        $dependencies = $constructor->getParameters();

        // Once we have all the constructor's parameters we can create each of the
        // dependency instances and then use the reflection instances to make a
        // new instance of this class, injecting the created dependencies in.
        $parameters = $this->keyParametersByArgument(
            $dependencies, $parameters
        );

        $instances = $this->getDependencies(
            $dependencies, $parameters
        );

        array_pop($this->buildStack);

        return $reflector->newInstanceArgs($instances);
    }

    /**
     * If extra parameters are passed by numeric ID, rekey them by argument name.
     *
     * @param array $dependencies
     * @param array $parameters
     *
     * @return array
     */
    protected function keyParametersByArgument(array $dependencies, array $parameters)
    {
        foreach ($parameters as $key => $value) {
            if (is_numeric($key)) {
                unset($parameters[$key]);

                $parameters[$dependencies[$key]->name] = $value;
            }
        }

        return $parameters;
    }

    /**
     * Resolve all of the dependencies from the ReflectionParameters.
     *
     * @param array $parameters
     * @param array $primitives
     *
     * @return array
     */
    protected function getDependencies(array $parameters, array $primitives = [])
    {
        $dependencies = [];
        foreach ($parameters as $parameter) {
            $dependency = $parameter->getClass();
            // If the class is null, it means the dependency is a string or some other
            // primitive type which we can not resolve since it is not a class and
            // we will just bomb out with an error since we have no-where to go.
            if (array_key_exists($parameter->name, $primitives)) {
                $dependencies[] = $primitives[$parameter->name];
            } elseif (is_null($dependency)) {
                $dependencies[] = $this->resolveNonClass($parameter);
            } else {
                $dependencies[] = $this->resolveClass($parameter);
            }
        }

        return $dependencies;
    }
    /**
     * Resolve a non-class hinted dependency.
     *
     * @param \ReflectionParameter $parameter
     *
     * @return mixed
     *
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    protected function resolveNonClass(\ReflectionParameter $parameter)
    {
        if ($parameter->isDefaultValueAvailable()) {
            return $parameter->getDefaultValue();
        }
        $message = "Unresolvable dependency resolving [$parameter] in class {$parameter->getDeclaringClass()->getName()}";
        throw new BindingResolutionException($message);
    }

    /**
     * Resolve a class based dependency from the container.
     *
     * @param \ReflectionParameter $parameter
     *
     * @return mixed
     *
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    protected function resolveClass(\ReflectionParameter $parameter)
    {
        try {
            return $this->make($parameter->getClass()->name);
        }
        // If we can not resolve the class instance, we will check to see if the value
        // is optional, and if it is we will return the optional parameter value as
        // the value of the dependency, similarly to how we do this with scalars.
        catch (BindingResolutionException $e) {
            if ($parameter->isOptional()) {
                return $parameter->getDefaultValue();
            }
            throw $e;
        }
    }

    /**
     * Get all dependencies for a given method.
     *
     * @param callable|string $callback
     * @param array           $parameters
     *
     * @return array
     */
    protected function getMethodDependencies($callback, array $parameters = [])
    {
        $dependencies = [];
        foreach ($this->getCallReflector($callback)->getParameters() as $parameter) {
            $this->addDependencyForCallParameter($parameter, $parameters, $dependencies);
        }

        return array_merge($dependencies, $parameters);
    }

    /**
     * Get the proper reflection instance for the given callback.
     *
     * @param callable|string $callback
     *
     * @return \ReflectionFunctionAbstract
     */
    protected function getCallReflector($callback)
    {
        if (is_string($callback) && strpos($callback, '::') !== false) {
            $callback = explode('::', $callback);
        }
        if (is_array($callback)) {
            return new \ReflectionMethod($callback[0], $callback[1]);
        }

        return new \ReflectionFunction($callback);
    }

    /**
     * Get the dependency for the given call parameter.
     *
     * @param \ReflectionParameter $parameter
     * @param array                $parameters
     * @param array                $dependencies
     *
     * @return mixed
     */
    protected function addDependencyForCallParameter(\ReflectionParameter $parameter, array &$parameters, &$dependencies)
    {
        if (array_key_exists($parameter->name, $parameters)) {
            $dependencies[] = $parameters[$parameter->name];
            unset($parameters[$parameter->name]);
        } elseif ($parameter->getClass()) {
            $dependencies[] = $this->make($parameter->getClass()->name);
        } elseif ($parameter->isDefaultValueAvailable()) {
            $dependencies[] = $parameter->getDefaultValue();
        }
    }

    public function resolveNew($class)
    {
        return new $class();
    }

    public static function get(array $params = [])
    {
        return static::$selfInstance = static::$selfInstance
            ?: new static($params);
    }
}