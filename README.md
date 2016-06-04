## PHP Constructor injection Container

5月24日从北京到杭州,下了高铁就去有赞面试~

到四点大半天仅吃了一根油条,面试都快饿昏了,胡乱扯了几句~  ~~可能没戏~~

晚上去西湖溜达了一圈~忆起一年前种种~

25号当即赶回北京,在飞驰的高铁窗边敲了一上午代码~把之前这个想法写了出来~

### interfaces.php
~~~php
<?php
namespace xiaofeng;

interface ServiceA {
    public function a();
}

interface ServiceB {
    public function b();
}

interface ServiceC {
    public function c();
}

interface ServiceD {
    public function d();
}

abstract class SingletonValid {
    static $count = 0;
    abstract function a();
}
~~~


### implements.php
~~~php
<?php
namespace xiaofeng;

class ServiceAImpl extends SingletonValid implements ServiceA {
    public function a() {
        return __METHOD__;
    }
}

class ServiceBImpl implements ServiceB {
    public function b() {
        return __METHOD__;
    }
}

class ServiceCImpl implements ServiceC {
    private $serviceA;
    private $serviceB;

    public function __construct(ServiceA $serviceA, ServiceB $serviceB) {
        $this->serviceA = $serviceA;
        $this->serviceB = $serviceB;
    }

    public function c() {
        return $this->serviceA->a() . " ==> " . $this->serviceB->b() . " ==> " . __METHOD__;
    }
}

class ServiceDImpl implements ServiceD {
    private $serviceC;

    public function __construct(ServiceC $serviceC) {
        $this->serviceC = $serviceC;
    }

    public function d() {
        return $this->serviceC->c() . " ==> " .  __METHOD__;
    }
}

class ModelA extends SingletonValid {
    private $serviceD;

    public function __construct(ServiceD $serviceD) {
        $this->serviceD = $serviceD;
        assert(++self::$count <= 1);
    }

    public function a() {
        return $this->serviceD->d() . " ==> " . __METHOD__;
    }
}

class XTools {
    public function x() {
        return __METHOD__;
    }
}
~~~


### app.php
~~~php
<?php
namespace xiaofeng;

require __DIR__ . "/../src/CtorIC.php";
require __DIR__ . "/interfaces.php";
require __DIR__ . "/implements.php";

// =============================== CONFIG ===========================================
// 1. 可以通过构造函数配置, 也可以通过数组访问配置
// 2. 接口对应的实现需要配置
// 3. 虚类对应的可实例化类也需要配置
// 4. 普通类不需要配置
// 5. 普通变量(标量,数组,对象实例)也不需要配置
// 6. 嵌套依赖会自动通过构造函数注入

// 通过构造参数传入配置
$di = new CtorIC([
    ServiceA::class => ServiceAImpl::class, // 接口 => 实现
]);

// 通过数组访问方式配置
$di[ServiceB::class] = ServiceBImpl::class;
$di[ServiceC::class] = ServiceCImpl::class;
$di[ServiceD::class] = ServiceDImpl::class;
$di[SingletonValid::class] = ModelA::class;  // 虚类 => 实现类

// 配置普通变量
$di["conf"] = [
    "name" => __NAMESPACE__,
    "version" => 0.1,
];

// 单独配置单例
$di->once(ServiceAImpl::class);
$di->once(ModelA::class);


return $di;
~~~


### main.php
~~~php
<?php
namespace xiaofeng;

/* @var $app CtorIC */
$app = require __DIR__ . "/app.php";

// 执行多次
$app(function(SingletonValid $model, XTools $tools, $conf) {
    echo $conf["name"] . " V" . $conf["version"], PHP_EOL;
    echo $model->a(), PHP_EOL;
    echo $tools->x(), PHP_EOL;
});

$app(function(SingletonValid $model, XTools $tools, $conf) {
    echo $conf["name"] . " V" . $conf["version"], PHP_EOL;
    echo $model->a(), PHP_EOL;
    echo $tools->x(), PHP_EOL;
});

$app(function(SingletonValid $model, XTools $tools, $conf) {
    echo $conf["name"] . " V" . $conf["version"], PHP_EOL;
    echo $model->a(), PHP_EOL;
    echo $tools->x(), PHP_EOL;
});


// 获取一个注入完成的闭包
$closure = $app->inject(function(SingletonValid $model, XTools $tools, $conf) {
    echo $conf["name"] . " V" . $conf["version"], PHP_EOL;
    echo $model->a(), PHP_EOL;
    echo $tools->x(), PHP_EOL;
});

$closure();
~~~
