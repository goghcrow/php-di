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