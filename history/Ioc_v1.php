<?php
/**
 * User: xiaofeng
 * Date: 2016/5/5
 * Time: 15:38
 */
namespace xiaofeng;
use RuntimeException;
require_once __DIR__ . "/../assert.php";


class DependencyNotFoundException extends RuntimeException {}

// 极简Ioc容器
// TODO: 组件注册可以完善成扫描+配置
// TODO: 对象惰性创建~
function box(array $ctx) {
    return function(\Closure $c) use($ctx) {
        $m = new \ReflectionMethod($c, "__invoke");
        $arguments = [];
        foreach($m->getParameters() as $parameter) {
            if(!isset($ctx[$parameter->name])) {
                throw new DependencyNotFoundException("no {$parameter->name} found!");
            }
            $arguments[] = $ctx[$parameter->name];
        }
        return call_user_func_array($c, $arguments);
    };
}

// => Example：
class Request
{
    public function __get($name) {
        $get = filter_input(INPUT_GET, $name);
        return $get === null ? filter_input(INPUT_POST, $name) : $get;
    }
}

class Response
{
    public function render($__tpl, array $__ctx) {
        ob_start();
        extract($__ctx);
        eval($__tpl);
        return ob_get_clean();
    }
}

// app 实例 启动
$app = box([
    "response" => new Response,
    "request" => new Request,
    // 其他组件~
]);

// 业务逻辑
$app(function($request, $response) {
    /* @var $request Request
     * @var $response Response */
    // 组件 通过参数注入~
    echo $response->render('echo "hello, $name";' , [
        "name" => $request->name,
    ]);
});

// file.php?name=xiaofeng
// out: hello, xiaofeng




//===============================================================================


abstract class Bean
{
    public $singleton = true;
    abstract public function create(/*...$args*/);
}


// TODO:

class Ioc implements \Countable, \ArrayAccess
{
    private $containers = [];

    public function __construct() {

    }

    protected function createBean($bean) {

    }

    public function lazyGet(/*$name, ...$args*/) {
        $args = func_get_args();
        $name = array_shift($args);
        $bean = $this[$name];

        if(is_object($bean) && $bean instanceof Bean) {

            if($bean->singleton) {
                if(isset($this->beanPool[$name])) {
                    return $this->beanPool[$name];
                } else {
                    $bean = call_user_func_array([$bean, "create"], $args);
                    $this->beanPool[$name] = $bean;
                    return $bean;
                }
            } else {
                $bean = call_user_func_array([$bean, "create"], $args);
            }
            $this[$name] = $bean;
        }
        return $bean;
    }

    public function offsetExists($name) {
        return isset($this->containers[$name]);
    }

    public function offsetGet($name) {
        if(!isset($this->containers[$name])) {
            throw new \RuntimeException("$name is not register");
        }
        return $this->containers[$name];
    }

    public function offsetSet($name, $value) {
        if($name === null) {
            throw new \InvalidArgumentException("should supply com name");
        }
        $this->containers[$name] = $value;
    }

    public function offsetUnset($name) {
        unset($this->containers[$name]);
    }

    public function count() {
        return count($this->containers);
    }

    public function __invoke(\Closure $c) {
        $m = new \ReflectionMethod($c, "__invoke");
        $arguments = [];
        foreach($m->getParameters() as $parameter) {
            $arguments[] = $this[$parameter->name];
        }
        return call_user_func_array($c, $arguments);
    }
}


$ioc = new Ioc();
$ioc["a"] = 1;
$ioc["b"] = 2;

echo $ioc(function($a, $b) {
    return $a + $b;
});