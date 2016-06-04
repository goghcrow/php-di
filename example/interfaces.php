<?php
/**
 * User: xiaofeng
 * Date: 2016/6/4
 * Time: 15:50
 */
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
