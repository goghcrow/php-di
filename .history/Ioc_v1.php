<?php
/**
 * User: xiaofeng
 * Date: 2016/5/5
 * Time: 15:38
 */
namespace xiaofeng;
use RuntimeException;
// require_once __DIR__ . "/../assert.php";


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
