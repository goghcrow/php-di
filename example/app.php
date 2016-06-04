<?php
/**
 * User: xiaofeng
 * Date: 2016/6/4
 * Time: 15:51
 */
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