<?php

namespace Iphp\Container;

use Closure;
use ReflectionClass;
use ReflectionParameter;

/**
 * 依赖注入容器（IoC/DI Container）
 *
 * 设计目标：
 * - 单例模式，使用 {@see Container::getInstance()} 获取全局实例
 * - 支持绑定（bind）和单例绑定（singleton）
 * - 支持手动注入实例（instance）
 * - 自动解析类构造函数的依赖
 * - 支持通过闭包或类名创建对象
 * - 允许在绑定时指定是否为共享/单例
 *
 * 示例：
 * ```php
 * use Iphp\Container\Container;
 *
 * // 获取全局容器
 * $c = Container::getInstance();
 *
 * // 绑定普通类
 * $c->bind('Foo', \App\Foo::class);
 *
 * // 绑定接口 -> 实现
 * $c->bind(\Psr\Log\LoggerInterface::class, \App\Logger\FileLogger::class);
 *
 * // 单例绑定
 * $c->singleton('bar', function($c) {
 *     return new \App\Bar($c->make('Foo'));
 * });
 *
 * // 手动注入已有实例
 * $c->instance('config', $configArray);
 *
 * // 解析对象
 * $foo = $c->make('Foo');
 * $bar = $c->make('bar');
 * ```
 */
class Container
{
    /** @var Container|null 全局单例实例 */
    protected static $instance;

    /**
     * 绑定信息数组
     *   [
     *     'abstractName' => [
     *         'concrete' => mixed, // 可以是类名或闭包
     *         'shared'   => bool,
     *     ],
     *   ]
     * @var array
     */
    protected $bindings = [];

    /**
     * 已解析的共享实例
     * @var array
     */
    protected $instances = [];

    /**
     * 获取容器单例
     *
     * @return Container
     */
    public static function getInstance(): Container
    {
        if (static::$instance === null) {
            static::$instance = new static();
        }
        return static::$instance;
    }

    /**
     * 私有构造，防止外部直接实例化
     */
    protected function __construct()
    {
    }

    /**
     * 克隆方法私有，保持单例
     */
    protected function __clone()
    {
    }

    /**
     * 防止通过反序列化创建多个实例
     */
    public function __wakeup()
    {
        throw new \Exception("Cannot unserialize a singleton.");
    }

    /**
     * 绑定一个抽象到具体实现
     *
     * @param string $abstract 抽象名称或接口
     * @param mixed $concrete 具体实现，类名或闭包。如果为null，则默认为$abstract。
     * @param bool $shared 是否为共享单例
     * @return void
     */
    public function bind(string $abstract, $concrete = null, bool $shared = false): void
    {
        if ($concrete === null) {
            $concrete = $abstract;
        }

        if (!($concrete instanceof Closure)) {
            $concrete = $this->getClosure($abstract, $concrete);
        }

        $this->bindings[$abstract] = [
            'concrete' => $concrete,
            'shared' => $shared,
        ];
    }

    /**
     * 绑定一个单例（共享）
     *
     * @param string $abstract
     * @param mixed $concrete
     * @return void
     */
    public function singleton(string $abstract, $concrete = null): void
    {
        $this->bind($abstract, $concrete, true);
    }

    /**
     * 注册一个已经创建好的实例
     *
     * @param string $abstract
     * @param mixed $instance
     * @return void
     */
    public function instance(string $abstract, $instance): void
    {
        $this->instances[$abstract] = $instance;
    }

    /**
     * 判断容器中是否有绑定/实例
     *
     * @param string $abstract
     * @return bool
     */
    public function has(string $abstract): bool
    {
        return isset($this->instances[$abstract]) || isset($this->bindings[$abstract]);
    }

    /**
     * 解析给定抽象并返回实例
     *
     * @param string $abstract
     * @param array $parameters
     * @return mixed
     * @throws \Exception
     */
    public function make(string $abstract, array $parameters = [])
    {
        // 已有共享实例直接返回
        if (isset($this->instances[$abstract])) {
            return $this->instances[$abstract];
        }

        // 是否有绑定
        if (isset($this->bindings[$abstract])) {
            $binding = $this->bindings[$abstract];
            $concrete = $binding['concrete'];

            $object = $concrete($this, $parameters);

            if ($binding['shared']) {
                $this->instances[$abstract] = $object;
            }

            return $object;
        }

        // 未绑定则尝试直接构建
        return $this->build($abstract, $parameters);
    }

    /**
     * 为给定的抽象返回一个闭包（如果已给出的是类名）
     *
     * @param string $abstract
     * @param mixed $concrete
     * @return Closure
     */
    protected function getClosure(string $abstract, $concrete): Closure
    {
        return function ($container, $params = []) use ($abstract, $concrete) {
            if ($abstract === $concrete) {
                return $container->build($concrete, $params);
            }

            return $container->make($concrete, $params);
        };
    }

    /**
     * 使用反射构建对象，并递归解析其依赖
     *
     * @param string $concrete
     * @param array $parameters
     * @return mixed
     * @throws \Exception
     */
    public function build(string $concrete, array $parameters = [])
    {
        if ($concrete instanceof Closure) {
            return $concrete($this, $parameters);
        }

        if (!class_exists($concrete)) {
            throw new \Exception("Cannot build class [{$concrete}] because it does not exist.");
        }

        $reflector = new ReflectionClass($concrete);

        if (!$reflector->isInstantiable()) {
            throw new \Exception("Class {$concrete} is not instantiable.");
        }

        $constructor = $reflector->getConstructor();

        if (is_null($constructor)) {
            return new $concrete;
        }

        $dependencies = $constructor->getParameters();
        $args = $this->resolveDependencies($dependencies);

        return $reflector->newInstanceArgs($args);
    }

    /**
     * 解析构造函数参数依赖
     *
     * @param ReflectionParameter[] $parameters
     * @return array
     * @throws \Exception
     */
    protected function resolveDependencies(array $parameters): array
    {
        $results = [];

        foreach ($parameters as $parameter) {
            $result = null;
            $type = $parameter->getType();

            if ($type && !$type->isBuiltin()) {
                $result = $this->make($type->getName());
            } elseif ($parameter->isDefaultValueAvailable()) {
                $result = $parameter->getDefaultValue();
            } else {
                throw new \Exception("Unresolvable dependency resolving [{$parameter->name}]");
            }

            $results[] = $result;
        }

        return $results;
    }
}
