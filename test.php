<?php
namespace xiaofeng;

class C {
    public function m($x, $y) {

    }
}


abstract class absClass {}
$clazz = new \ReflectionClass(absClass::class);
try {
    $clazz->newInstanceWithoutConstructor();
    assert(false);
} catch (\Throwable $e) {
    assert($e);
}

class privateCtor {
    private function __construct(){}
}
$clazz = new \ReflectionClass(privateCtor::class);
try {
    $clazz->newInstance();
    assert(false);
} catch (\Throwable $e) {
    assert($e);
}


Interface I {}
class A {}
class B extends A implements I{}
assert(is_subclass_of(B::class, A::class) === true);
// 类不能存在返回false
assert(is_subclass_of("NOT_EXIST", "NOT_EXIST") === false);


function xxx() {
    return __FUNCTION__;
}
assert(xxx() === __NAMESPACE__ . "\\xxx");

interface TestInterface {

}


function foo(TestInterface $a) { }

$functionReflection = new \ReflectionFunction('\xiaofeng\foo');
$parameters = $functionReflection->getParameters();
$aParameter = $parameters[0];

$type = $aParameter->getClass()->name;
assert(interface_exists($type));
assert($type === TestInterface::class);