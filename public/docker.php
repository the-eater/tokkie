<?php

# fwrite(fopen("php://stderr", 'w'), print_r([$_GET, $_POST, $_SERVER], true));

require __DIR__ . '/../vendor/autoload.php';

$docker = new \Eater\Tokkie\Docker(new \Eater\Tokkie\Application(include(__DIR__ . '/../config/tokkie.php')));
$docker->handle();
