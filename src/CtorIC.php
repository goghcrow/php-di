<?php
/**
 * User: xiaofeng
 * Date: 2016/5/ 25
 * Time: 10:00
 * @G46(杭州-北京)
 */

namespace xiaofeng;

require_once __DIR__ . DIRECTORY_SEPARATOR . "Call.php";

use Iterator;
use ArrayAccess;
use Countable;
use Closure;
use ReflectionMethod;
use ReflectionClass;
use RuntimeException;


/**
 * class CtorIC : Constructor Injection Container
 * @package xiaofeng
 *
 * 主要实现基于类构造函数实现依赖注入的容易
 * [不负责autoload]
 * [配置规则]
 * 1. 可以通过构造函数配置, 也可以通过数组访问配置
 * 2. 接口对应的实现需要配置
 * 3. 虚类对应的可实例化类也需要配置
 * 4. 普通类不需要配置
 * 5. 普通变量(标量,数组,对象实例)也不需要配置
 * 6. 嵌套依赖会自动通过构造函数注入
 * [使用方式参见example]
 */
class CtorIC implements ArrayAccess, Countable, Iterator{

    /**
     * 依赖映射容器
     * key => [className|interface]::class | any other string
     * value => mixed
     * @var array
     */
    protected $dependenciesMap;

    /**
     * 已实例化对象容器
     * k => className::class
     * v => new ClassName(...$args)
     * @var array
     */
    protected $instancesMap;

    /**
     * 被声明为单例模式的className::class
     * @var array
     */
    protected $singletonClassNames;

    /**
     * CtorIC constructor.
     * @param array $diConf
     */
    public function __construct(array $diConf = []) {
        $this->dependenciesMap = $diConf;
        $this->instancesMap = [];
        $this->singletonClassNames = [];
    }

    /**
     * 声明该类在容器内只实例化一次
     * @param string $clazz className::class
     */
    public function once($clazz) {
        $this->singletonClassNames[$clazz] = true;
    }

    /**
     * 自动注入Closure参数,然后执行
     * @param Closure $closure
     * @return mixed
     */
    public function __invoke(Closure $closure) {
        $method = new ReflectionMethod($closure, "__invoke");
        $arguments = $this->getArguments($method);
        return call_user_func_array($closure, $arguments);
    }

    /**
     * @param $key
     * @return mixed
     */
    public function make($key) {
        if (class_exists($key, true) || interface_exists($key, true)) {
            return $this->instance(new ReflectionClass($key));
        } else if (isset($this->dependenciesMap[$key])){
            return $this->dependenciesMap[$key];
        }
        throw new CtorICException("Can not make Key \${$key}");
    }

    /**
     * 将callable参数注入后 返回一个无参闭包对象
     * @param callable $callable
     * @return Closure
     */
    public function inject(callable $callable) {
        try {
            $closure = Call::toClosure($callable);
        } catch (CallException $ex) {
            throw new CtorICException($ex->getMessage(), $ex->getCode());
        }

        $method = new ReflectionMethod($closure, "__invoke");
        $arguments = $this->getArguments($method);
        return function() use($closure, $arguments) {
            return call_user_func_array($closure, $arguments);
        };
    }

    /**
     * 根据配置,获取反射方法实参
     * @param ReflectionMethod $method
     * @param [] $circleCheck
     * @return array
     */
    protected function getArguments(ReflectionMethod $method, $circleCheck = []) {
        $arguments = [];
        foreach($method->getParameters() as $parameter) {
            // TODO: php7 if($parameter->hasType()) { $reflectionType = $parameter->getType(); }
            $parameterClazz = $parameter->getClass();
            if ($parameterClazz != null) {
                // 有类型提示 function(TypeHint $para, ...), 根据TypeHint查找依赖
                $arguments[] = $this->instance($parameterClazz, $circleCheck);
            } else {
                // 无类型提示 function($argName) 根据实参变量名"argName"查找依赖
                $name = $parameter->name;
                if (!isset($this->dependenciesMap[$name])) {
                    throw new CtorICException("ParameterName \${$name} Not Found");
                }
                $arguments[] = $this->dependenciesMap[$name];
            }
        }
        return $arguments;
    }

    /* 以下重载实现语法糖~ */

    public function offsetExists($offset){ return isset($this->dependenciesMap[$offset]); }
    public function offsetGet($offset) { return isset($this->dependenciesMap[$offset]) ? $this->dependenciesMap[$offset] : null; }
    public function offsetSet($offset, $value) { $this->dependenciesMap[$offset] = $value; }
    public function offsetUnset($offset) { unset($this->dependenciesMap[$offset]); }
    public function count() { return count($this->dependenciesMap); }
    public function current() { return current($this->dependenciesMap); }
    public function next() { return next($this->dependenciesMap); }
    public function key() { return key($this->dependenciesMap); }
    public function valid() { return current($this->dependenciesMap) !== false; }
    public function rewind() { return reset($this->dependenciesMap); }


    /**
     * 根据反射类构造函数自动注入依赖实例化
     * @param ReflectionClass $clazz
     * @param array $circleCheck
     * @return object
     */
    protected function _instanceNew(ReflectionClass $clazz, $circleCheck) {
        if (!$clazz->isInstantiable()) {
            throw new CtorICException("Cannot instantiate class {$clazz->name}");
        }
        $ctorMethod = $clazz->getConstructor();
        // PHP7: try {} catch (Throwable $e) { throw new IoCException($e->getMessage(), $e->getCode()); }
        if ($ctorMethod !== null) {
            return $clazz->newInstanceArgs($this->getArguments($ctorMethod, $circleCheck));
        } else {
            return $clazz->newInstanceWithoutConstructor();
        }
    }

    /**
     * 单例模式实例化对象
     * @param ReflectionClass $clazz
     * @param $circleCheck
     * @return object
     */
    protected function _instanceOnce(ReflectionClass $clazz, $circleCheck) {
        $name = $clazz->name;
        if (!isset($this->instancesMap[$name])) {
            $this->instancesMap[$name] = $this->_instanceNew($clazz, $circleCheck);
        }
        return $this->instancesMap[$name];
    }

    /**
     * 根据配置自动实例化对象
     * @param ReflectionClass $clazz
     * @param array $circleCheck
     * @return object
     */
    protected function _instance(ReflectionClass $clazz, $circleCheck) {
        $isSingleton = isset($this->singletonClassNames[$clazz->name]);
        if ($isSingleton) {
            return $this->_instanceOnce($clazz, $circleCheck);
        } else {
            return $this->_instanceNew($clazz, $circleCheck);
        }
    }

    /**
     * @param string $depClassName
     * @param array $toCheck
     */
    private function circleDependencyCheck($depClassName, &$toCheck) {
        if (in_array($depClassName, $toCheck, true)) {
            $toCheck[] = $depClassName;
            $path = implode(" -> ", $toCheck);
            throw new CircleDependencyException("Found Circle Dependency In Path $path");
        } else {
            $toCheck[] = $depClassName;
        }
    }

    /**
     * 根据配置,从接口或者类获取依赖对象
     * @param ReflectionClass $parameterClazz
     * @param array $circleCheck
     * @return object
     */
    protected function instance(ReflectionClass $parameterClazz, $circleCheck = []) {
        $clazzName = $parameterClazz->name;
        if ($parameterClazz->isInterface()) {
            if (!isset($this->dependenciesMap[$clazzName])) {
                throw new CtorICException("Interface \"{$clazzName}\" Implements Class Not Found");
            }
            // 处理多态
            $implementedClazzName = (string) $this->dependenciesMap[$clazzName];
            if (!class_exists($implementedClazzName, true)) {
                throw new CtorICException("Interface \"{$clazzName}\" Implements Class \"{$implementedClazzName}\" Not Found");
            }
            if (!is_subclass_of($implementedClazzName, $clazzName)) {
                throw new CtorICException("{$implementedClazzName} Does Not Implements {$clazzName}");
            }

            $this->circleDependencyCheck($implementedClazzName, $circleCheck);
            return $this->_instance(new ReflectionClass($implementedClazzName), $circleCheck);
        } else {
            // 处理多态
            if (isset($this->dependenciesMap[$clazzName])) {
                $subClazzName = (string) $this->dependenciesMap[$clazzName];
                // 类不存在 is_subclass_of 返回 false
                if (!is_subclass_of($subClazzName, $clazzName)) {
                    throw new CtorICException("{$subClazzName} Is Not SubClass Of {$clazzName}");
                }

                $this->circleDependencyCheck($subClazzName, $circleCheck);
                return $this->_instance(new ReflectionClass($subClazzName), $circleCheck);
            } else {

                $this->circleDependencyCheck($parameterClazz->getName(), $circleCheck);
                return $this->_instance($parameterClazz, $circleCheck);
            }
        }
    }
}

class CtorICException extends RuntimeException {}
class CircleDependencyException extends RuntimeException {}