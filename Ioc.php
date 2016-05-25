<?php
/**
 * User: xiaofeng
 * Date: 2016/5/ 25
 * Time: 10:00
 * @G46(杭州-北京)
 */

namespace xiaofeng;

use Iterator;
use ArrayAccess;
use Countable;
use Closure;
use ReflectionMethod;
use ReflectionClass;
use ReflectionException;
use RuntimeException;

/**
 * Class IoC 依赖注入容器
 * @package xiaofeng
 *
 * [不负责autoload]
 * [使用方式参见example]
 * [配置规则]
 * 1. 可以通过构造函数配置, 也可以通过数组访问配置
 * 2. 接口对应的实现需要配置
 * 3. 虚类对应的可实例化类也需要配置
 * 4. 普通类不需要配置
 * 5. 普通变量(标量,数组,对象实例)也不需要配置
 * 6. 嵌套依赖会自动通过构造函数注入
 */
class IoC implements ArrayAccess, Countable, Iterator{
    protected $dependencies;
    public $singletonClassNames = [];
    protected $instances;

    public function __construct(array $dependencies = []) {
        $this->dependencies = $dependencies;
        $this->instances = [];
    }
    
    protected function isImplements($clazz, $interface) {
        try {
            $refc = new ReflectionClass($clazz);
            return $refc->implementsInterface($interface);
        } catch (ReflectionException $ex) {
            return false;
        }
    }

    protected function _instanceNew(ReflectionClass $clazz) {
        if(!$clazz->isInstantiable()) {
            throw new IoCException("Cannot instantiate class {$clazz->name}");
        }
        $ctorMethod = $clazz->getConstructor();
        // PHP7: try {} catch (Throwable $e) { throw new IoCException($e->getMessage(), $e->getCode()); }
        if($ctorMethod !== null) {
            return $clazz->newInstanceArgs($this->getArguments($ctorMethod));
        } else {
            return $clazz->newInstanceWithoutConstructor();
        }
    }

    protected function _instanceOnce(ReflectionClass $clazz) {
        $name = $clazz->name;
        if(!isset($this->instances[$name])) {
            $this->instances[$name] = $this->_instanceNew($clazz);
        }
        return $this->instances[$name];
    }

    protected function _instance(ReflectionClass $clazz) {
        $isSingleton = isset($this->singletonClassNames[$clazz->name]);
        if($isSingleton) {
            return $this->_instanceOnce($clazz);
        } else {
            return $this->_instanceNew($clazz);
        }
    }

    protected function instance(ReflectionClass $parameterClazz) {
        $clazzName = $parameterClazz->name;
        if(interface_exists($clazzName)) {
            if(!isset($this->dependencies[$clazzName])) {
                throw new IoCException("Interface \"{$clazzName}\" Implements Class Not Found");
            }
            // 处理多态
            $implementedClazzName = (string) $this->dependencies[$clazzName];
            if(!class_exists($implementedClazzName)) {
                throw new IoCException("Interface \"{$clazzName}\" Implements Class \"{$implementedClazzName}\" Not Found");
            }
            if(!$this->isImplements($implementedClazzName, $clazzName)) {
                throw new IoCException("{$implementedClazzName} Does Not Implements {$clazzName}");
            }
            return $this->_instance(new ReflectionClass($implementedClazzName));
        } else if(class_exists($clazzName)) {
            // 处理多态
            if(isset($this->dependencies[$clazzName])) {
                $subClazzName = (string) $this->dependencies[$clazzName];
                // 类不存在 is_subclass_of 返回 false
                if(!is_subclass_of($subClazzName, $clazzName)) {
                    throw new IoCException("{$subClazzName} Is Not SubClass Of {$clazzName}");
                }
                return $this->_instance(new ReflectionClass($subClazzName));
            } else {
                return $this->_instance($parameterClazz);
            }
        } else {
            throw new IoCException("ParameterType {$clazzName} Not Found");
        }
    }

    /**
     * 声明单例模式
     * @param string $clazz <InstantiableClass>::class
     */
    public function once($clazz) {
        $this->singletonClassNames[$clazz] = true;
    }

    /**
     * 注入参数实例
     * @param ReflectionMethod $method
     * @return array
     */
    public function getArguments(ReflectionMethod $method) {
        $arguments = [];
        foreach($method->getParameters() as $parameter) {
            // TODO: php7 if($parameter->hasType()) { $reflectionType = $parameter->getType(); }
            $parameterClazz = $parameter->getClass();
            if($parameterClazz != null) {
                // 有类型提示 function(TypeHint $para, ...), 根据TypeHint查找依赖
                $arguments[] = $this->instance($parameterClazz);
            } else {
                // 无类型提示 function($argName) 根据实参变量名"argName"查找依赖
                $name = $parameter->name;
                if(!isset($this->dependencies[$name])) {
                    throw new IoCException("ParameterName \${$name} Not Found");
                }
                $arguments[] = $this->dependencies[$name];
            }
        }
        return $arguments;
    }

    public function __invoke(Closure $closure) {
        $method = new \ReflectionMethod($closure, "__invoke");
        $arguments = $this->getArguments($method);
        return call_user_func_array($closure, $arguments);
    }

    public function offsetExists($offset){ return isset($this->dependencies[$offset]); }
    public function offsetGet($offset) { return isset($this->dependencies[$offset]) ? $this->dependencies[$offset] : null; }
    public function offsetSet($offset, $value) { $this->dependencies[$offset] = $value; }
    public function offsetUnset($offset) { unset($this->dependencies[$offset]); }
    public function count() { return \count($this->dependencies); }
    public function current() { return \current($this->dependencies); }
    public function next() { return \next($this->dependencies); }
    public function key() { return \key($this->dependencies); }
    public function valid() { return \current($this->dependencies) !== false; }
    public function rewind() { return \reset($this->dependencies); }
}

class IoCException extends RuntimeException {}