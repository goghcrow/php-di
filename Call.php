<?php
/**
 * User: xiaofeng
 * Date: 2016/5/26
 * Time: 15:23
 */

namespace xiaofeng;
use Closure;
use ReflectionFunction;
use ReflectionClass;
use ReflectionMethod;
use ReflectionException;
use RuntimeException;

class Call {

    protected static function getObjectCallableClosure($callable) {
        if ($callable instanceof Closure) {
            return $callable;
        }

        // is_callable(class with method __invoke) === true
        $method = new ReflectionMethod($callable, "__invoke");
        return $method->getClosure($callable);
    }

    protected static function getStringCallableClosure($callable) {
        if (strpos($callable, "::") === false) {
            $function = new ReflectionFunction($callable);
            return $function->getClosure();
        }

        list($clazz, $method) = explode("::", $callable);
        // static::method
        if ($clazz === "static") {
            // TODO
            // debug_print_backtrace(); // #2
            throw new CallException("Still Not Implement");
            // $clazz = get_called_class();
        }

        $method = new ReflectionMethod($clazz, $method);
        return $method->getClosure();
    }

    protected static function getArrayCallableClosure($callable) {
        list($clazz, $method) = $callable;

        if(strpos($method, "::") === false) {
            $method = new ReflectionMethod($clazz, $method);
            return $method->getClosure(is_object($clazz) ? $clazz : null);
        }

        list($clazzScope, $method) = explode("::", $method);

        if ($clazzScope === "self") {
            $method = new ReflectionMethod($clazz, $method);
            return $method->getClosure(is_object($clazz) ? $clazz : null);
        }

        if ($clazzScope === "parent") {
            if (is_object($clazz)) {
                $subClazz = new ReflectionClass($clazz);
                $parentClazz = $subClazz->getParentClass();
                $parentMethod = $parentClazz->getMethod($method);
                return $parentMethod->getClosure($clazz); // non-static method ===> non-static Closure
            } else if (is_string($clazz)) {
                $clazz = get_parent_class($clazz);
                $method = new ReflectionMethod($clazz, $method);
                return $method->getClosure(null); // static method ==> static Closure
            }
        }

        if ($clazzScope === "static") {
            // TODO
            throw new CallException("Still Not Implement");
        }

        throw new CallException("Can Not Get A Closure From The Array Callable");
    }

    /**
     * callable ===> Closure
     * @param $callable
     * @return Closure
     */
    public static function getClosure($callable) {
        if (!is_callable($callable)) {
            throw new CallException("First Argument Is Not A Callable");
        }

        try {
            if (is_object($callable)) {
                return self::getObjectCallableClosure($callable);
            }

            if (is_string($callable)) {
                return self::getStringCallableClosure($callable);
            }

            if (is_array($callable)) {
                return self::getArrayCallableClosure($callable);
            }
        } catch (ReflectionException $ex) {
            throw new CallException($ex->getMessage(), $ex->getCode());
        }

        throw new CallException("Can Not Get Closure From The First Argument");
    }
}

class CallException extends RuntimeException {}