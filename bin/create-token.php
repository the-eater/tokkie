<?php

require __DIR__ . '/../vendor/autoload.php';

$app = new \Eater\Tokkie\Application(include(__DIR__ . '/../config/tokkie.php'));

echo $app->createToken($argv[1]);