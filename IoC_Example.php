<?php
namespace xiaofeng;
require __DIR__ . "/Ioc.php";

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


// 配置
// 1. 可以通过构造函数配置, 也可以通过数组访问配置
// 2. 接口对应的实现需要配置
// 3. 虚类对应的可实例化类也需要配置
// 4. 普通类不需要配置
// 5. 普通变量(标量,数组,对象实例)也不需要配置
// 6. 嵌套依赖会自动通过构造函数注入
$box = new IoC([
    ServiceA::class => ServiceAImpl::class,
    ServiceB::class => ServiceBImpl::class,
    ServiceC::class => ServiceCImpl::class,
    ServiceD::class => ServiceDImpl::class,
]);

$box[SingletonValid::class] = ModelA::class;

$box["conf"] = [
    "name" => __NAMESPACE__,
    "version" => 0.1,
];
// 单例需要单独配置
$box->once(ServiceAImpl::class);
$box->once(ModelA::class);

// APP
$box(function(SingletonValid $model, XTools $tools, $conf) {
//    echo $conf["name"] . " V" . $conf["version"], PHP_EOL;
//    echo $model->a(), PHP_EOL;
//    echo $tools->x(), PHP_EOL;
});
$box(function(SingletonValid $model, XTools $tools, $conf) {
//    echo $conf["name"] . " V" . $conf["version"], PHP_EOL;
//    echo $model->a(), PHP_EOL;
//    echo $tools->x(), PHP_EOL;
});
$box(function(SingletonValid $model, XTools $tools, $conf) {
//    echo $conf["name"] . " V" . $conf["version"], PHP_EOL;
//    echo $model->a(), PHP_EOL;
//    echo $tools->x(), PHP_EOL;
});