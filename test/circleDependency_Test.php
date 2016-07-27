<?php
/**
 * Created by PhpStorm.
 * User: chuxiaofeng
 * Date: 16/7/27
 * Time: 下午9:26
 */
namespace xiaofeng;

require __DIR__ . "/../src/CtorIC.php";


interface IA {}
interface IB {}

class A implements IA {
    public function __construct(B $b) {
    }
}

class B implements IB {
    public function __construct(A $b) {
    }
}


$di = new CtorIC([
    IA::class => A::class,
    IB::class => B::class,
]);

try {
    $di(function(IA $a) { });
    assert(false);
} catch (CircleDependencyException $ex) {
    assert($ex->getMessage() === "Found Circle Dependency In Path xiaofeng\\A -> xiaofeng\\B -> xiaofeng\\A");
}


//////////////////////////////////////////////////////////////////////////////////////////

class AA {
    function __construct(BB $b){}
}

class BB {
    function __construct(CC $c){}
}

class CC {
    function __construct(AA $a){}
}

class DD {
    function __construct(EE $a){}
}

class EE {
    function __construct(FF $f, AA $a){}
}

class FF {}


/*
xiaofeng\DD -> xiaofeng\EE -> xiaofeng\AA -> xiaofeng\BB -> xiaofeng\CC -> xiaofeng\AA'
*/

$di = new CtorIC;
try {
    $di->make(DD::class);
    assert(false);
} catch (CircleDependencyException $ex) {
    assert($ex->getMessage() ===
        'Found Circle Dependency In Path xiaofeng\DD -> xiaofeng\EE -> xiaofeng\AA -> xiaofeng\BB -> xiaofeng\CC -> xiaofeng\AA');
}