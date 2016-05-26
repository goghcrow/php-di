<?php
/**
 * User: xiaofeng
 * Date: 2016/5/26
 * Time: 15:19
 */

namespace phpnet;

// @from http://php.net/manual/en/language.types.callable.php#118032

/**
 * The callable types and normalizations are given in the table below:
 *
 *  Callable                        | Normalization                   | Type
 * ---------------------------------+---------------------------------+--------------
 *  function (...) use (...) {...}  | function (...) use (...) {...}  | 'closure'
 *  $object                         | $object                         | 'invocable'
 *  "function"                      | "function"                      | 'function'
 *  "class::method"                 | ["class", "method"]             | 'static'
 *  ["class", "parent::method"]     | ["parent of class", "method"]   | 'static'
 *  ["class", "self::method"]       | ["class", "method"]             | 'static'
 *  ["class", "method"]             | ["class", "method"]             | 'static'
 *  [$object, "parent::method"]     | [$object, "parent::method"]     | 'object'
 *  [$object, "self::method"]       | [$object, "method"]             | 'object'
 *  [$object, "method"]             | [$object, "method"]             | 'object'
 * ---------------------------------+---------------------------------+--------------
 *  other callable                  | idem                            | 'unknown'
 * ---------------------------------+---------------------------------+--------------
 *  not a callable                  | null                            | false
 *
 * If the "strict" parameter is set to true, additional checks are
 * performed, in particular:
 *  - when a callable string of the form "class::method" or a callable array
 *    of the form ["class", "method"] is given, the method must be a static one,
 *  - when a callable array of the form [$object, "method"] is given, the
 *    method must be a non-static one.
 *
 */
function callableType($callable, $strict = true, callable& $norm = null) {
    if (!is_callable($callable)) {
        switch (true) {
            case is_object($callable):
                $norm = $callable;
                return 'Closure' === get_class($callable) ? 'closure' : 'invocable';
            case is_string($callable):
                $m    = null;
                if (preg_match('~^(?<class>[a-z_][a-z0-9_]*)::(?<method>[a-z_][a-z0-9_]*)$~i', $callable, $m)) {
                    list($left, $right) = [$m['class'], $m['method']];
                    if (!$strict || (new \ReflectionMethod($left, $right))->isStatic()) {
                        $norm = [$left, $right];
                        return 'static';
                    }
                } else {
                    $norm = $callable;
                    return 'function';
                }
                break;
            case is_array($callable):
                $m = null;
                if (preg_match('~^(:?(?<reference>self|parent)::)?(?<method>[a-z_][a-z0-9_]*)$~i', $callable[1], $m)) {
                    if (is_string($callable[0])) {
                        if ('parent' === strtolower($m['reference'])) {
                            list($left, $right) = [get_parent_class($callable[0]), $m['method']];
                        } else {
                            list($left, $right) = [$callable[0], $m['method']];
                        }
                        if (!$strict || (new \ReflectionMethod($left, $right))->isStatic()) {
                            $norm = [$left, $right];
                            return 'static';
                        }
                    } else {
                        if ('self' === strtolower($m['reference'])) {
                            list($left, $right) = [$callable[0], $m['method']];
                        } else {
                            list($left, $right) = $callable;
                        }
                        if (!$strict || !(new \ReflectionMethod($left, $right))->isStatic()) {
                            $norm = [$left, $right];
                            return 'object';
                        }
                    }
                }
                break;
        }
        $norm = $callable;
        return 'unknown';
    }
    $norm = null;
    return false;
}