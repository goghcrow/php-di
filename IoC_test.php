<?php
namespace xiaofeng;
require __DIR__ . "/Ioc.php";

$box = new IoC;
define("TEST_VAR_NAME", "xiaofeng");


//=================================================================
// 添加标量与数组依赖
$box["x"] = 1;
$box["conf"] = [
    "v" => 2,
];

assert($box(function($x, $conf) {
    return $x + $conf["v"];
}) === 3);

try {
    $box(function($not_exist) {});
    assert(false);
} catch (IoCException $ex) {
    assert($ex);
}

//=================================================================
// 接口实现无构造函数
interface IHelloService1 {
    public function hello($name);
}

class HelloService1 implements IHelloService1 {
    public function hello($name) {
        return "hello $name";
    }
}

// 添加借接口服务依赖
$box[IHelloService1::class] = HelloService1::class;

assert($box(function(IHelloService1 $hello) {
   return $hello->hello(TEST_VAR_NAME);
}) === "hello " . TEST_VAR_NAME);

$closure = $box->inject(function(IHelloService1 $hello) {
    return $hello->hello(TEST_VAR_NAME);
});

assert($closure() === "hello " . TEST_VAR_NAME);

//=================================================================
// 接口实现有构造函数
interface IHelloService2 {
    public function hello();
}

class HelloService2 implements IHelloService2 {
    private $name;
    public function __construct($name) {
        assert($name === TEST_VAR_NAME);
        $this->name = $name;
    }

    public function hello() {
        return "hello {$this->name}";
    }
}

$box["name"] = TEST_VAR_NAME;

// 添加借接口服务依赖
$box[IHelloService2::class] = HelloService2::class;

assert($box(function(IHelloService2 $hello) {
        return $hello->hello();
    }) === "hello " . TEST_VAR_NAME);


//=================================================================
interface ITest1 {}
class Test1 {}

$box[ITest1::class] = "Not_EXIST_CLASS";
try {
    $box(function(ITest1 $test) {});
    assert(false);
} catch (IoCException $ex) {
    assert($ex);
}

//=================================================================
interface ITest2 {}
class Test2 {}
$box[ITest2::class] = Test2::class;
try {
    $box(function(ITest2 $test) {});
    assert(false);
} catch (IoCException $ex) {
    assert($ex);
}

//=================================================================
// 类 有构造函数
class HelloService3 {
    private $helloServ;
    public function __construct(IHelloService2 $hello) {
        $this->helloServ = $hello;
    }
    public function hello() {
        return $this->helloServ->hello();
    }
}

// 类自动注入,无需添加
// $box[HelloService3::class] =

assert($box(function(HelloService3 $hello) {
    return $hello->hello();
}) === "hello " . TEST_VAR_NAME);

//=================================================================
// 类 子类有构造函数

abstract class baseHelloService {
    protected $helloServ;
    public function __construct(IHelloService2 $hello) {
        $this->helloServ = $hello;
    }
    abstract  public function hello();
}

class HelloService4 extends baseHelloService {
    private $who;
    public function __construct(IHelloService2 $hello, $who) {
        parent::__construct($hello);
        $this->who = $who;
    }

    public function hello() {
        return $this->who . " say " . $this->helloServ->hello();
    }
}

$box["who"] = "someone";

// 父类或者虚类需要指定实现类
$box[HelloService3::class] = HelloService4::class;

assert($box(function(HelloService4 $hello) {
    return $hello->hello();
}) === $box["who"] . " say hello " . TEST_VAR_NAME);

//=================================================================
// singleton
interface SingletonService {
    public function getCount();
}
class Singleton implements SingletonService {
    static $count = 0;
    public function __construct($name) {
        assert(++self::$count <= 1);
    }
    public function getCount() {
        return self::$count;
    }
}

$box[SingletonService::class] = Singleton::class;
$box->once(Singleton::class); // 只实例化一次

assert($box(function(SingletonService $singleton) {
    return $singleton->getCount();
}) === 1);
assert($box(function(SingletonService $singleton) {
        return $singleton->getCount();
    }) === 1);
assert($box(function(SingletonService $singleton) {
        return $singleton->getCount();
    }) === 1);

//=================================================================

// inject
interface XService {
    public function X($arg);
}

class XServiceImpl implements XService {
    public function X($arg) {
        return __METHOD__ . " $arg";
    }
}

$box = new IoC;
$box[XService::class] = XServiceImpl::class;
// $box->once(XServiceImpl::class);

function doSomething(XService $xService) {
    return $xService->x(__FUNCTION__);
}

$doSomething = $box->inject(__NAMESPACE__ . "\\doSomething");

assert($doSomething() === "xiaofeng\\XServiceImpl::X xiaofeng\\doSomething");