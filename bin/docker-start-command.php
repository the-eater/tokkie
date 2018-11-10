<?php

$config = include(__DIR__ . '/../config/tokkie.php');

$cmd = ['docker', 'create'];

$cmd[] = '-p';
$cmd[] = '5000:5000';
$cmd[] = '-v';
$cmd[] = escapeshellarg(realpath(__DIR__ . '/../storage/bundle') . ':/bundle:ro');
$cmd[] = '--env';
$cmd[] = 'REGISTRY_AUTH_TOKEN_REALM=' . escapeshellarg($config['docker']['token-dispenser']);
$cmd[] = '--env';
$cmd[] = 'REGISTRY_AUTH_TOKEN_SERVICE=' . escapeshellarg($config['docker']['service']);
$cmd[] = '--env';
$cmd[] = 'REGISTRY_AUTH_TOKEN_ISSUER=' . escapeshellarg($config['docker']['issuer']);
$cmd[] = '--env';
$cmd[] = 'REGISTRY_AUTH_TOKEN_ROOTCERTBUNDLE=' . escapeshellarg('/bundle/tokkie.crt');

$cmd[] = 'registry:2';

echo implode(" ", $cmd) . "\n";