<?php
namespace xiaofeng;
require __DIR__ . "/../src/Call.php";

function my_callback_function() {
    return "hello world!";
}
$c = Call::toClosure(__NAMESPACE__ . "\\my_callback_function");
assert($c() === "hello world!");

class MyClass {
    public $prop = "Hello World!";
    public static $staticProp = "Static Hello World!";
    public function myCallbackMethod() {
        return $this->prop;
    }
    public static function myStaticCallbackMethod() {
        return static::$staticProp;
    }

    public static $invokeProp = "Invoke Hello World";
    public function __invoke() {
        return self::$invokeProp;
    }
}

$myClass = new MyClass;

$c = Call::toClosure([MyClass::class, "myStaticCallbackMethod"]);
assert($c() === MyClass::$staticProp);

$c = Call::toClosure(MyClass::class . "::myStaticCallbackMethod");
assert($c() === MyClass::$staticProp);

$c = Call::toClosure([$myClass, "myCallbackMethod"]);
assert($c() === $myClass->prop);


MyClass::$staticProp = "New Static Hello World!";
$myClass->prop = "New Hello World!";


$c = Call::toClosure([MyClass::class, "myStaticCallbackMethod"]);
assert($c() === MyClass::$staticProp);

$c = Call::toClosure(MyClass::class . "::myStaticCallbackMethod");
assert($c() === MyClass::$staticProp);

$c = Call::toClosure([$myClass, "myCallbackMethod"]);
assert($c() === $myClass->prop);

$newMyClass = new MyClass;
$newMyClass->prop = "New Hello World!";
$c = Call::toClosure([$newMyClass, "myCallbackMethod"]);
assert($c() === $newMyClass->prop);


$c = Call::toClosure($myClass);
assert($c() === MyClass::$invokeProp);

class A {
    public static $who = "A";
    public static function who() {
        return self::$who;
    }
    public $name = "A";
    public function hello() {
        return "A Hello " . $this->name;
    }
    public function helloWithStatic() {
        return "A Hello " . self::$who;
    }
    public function testStatic() {
        return call_user_func("static::hello");
        // $c = Call::getClosure("static::hello");
        // return $c();
    }
}

class B extends A {
    public static $who = "B";
    public static function who() {
        return self::$who;
    }
    public $name = "B";
    public function hello() {
        return "B Hello " . $this->name;
    }
    public function helloWithStatic() {
        return "B Hello " . self::$who;
    }
}

$c = Call::toClosure([B::class, "parent::who"]);
assert($c() === "A");

$c = Call::toClosure([B::class, "self::who"]);
assert($c() === "B");


// !!! 非静态量 $this绑定到 new B
$c = Call::toClosure([new B, "parent::hello"]);
assert($c() === "A Hello B");


// !!! 静态变量 保持方法内作用域
$c = Call::toClosure([new B, "parent::helloWithStatic"]);
assert($c() === "A Hello A");

//$a = new A;
//echo $a->testStatic();
