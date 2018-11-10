<?php

require __DIR__ . '/../vendor/autoload.php';

$config = include(__DIR__ . '/../config/tokkie.php');

$fail = false;
foreach (array_keys(array_filter(array_map('file_exists', $config['files']))) as $type) {
    echo "ERROR: {$type} file already exists at {$config['files'][$type]}\n";
}

if ($fail) {
    exit(1);
}

$privateKey = openssl_pkey_new($config['openssl.config']);
$csr = openssl_csr_new($config['cert.dn'], $privateKey, $config['openssl.config']);
$x509 = openssl_csr_sign($csr, null, $privateKey, $config['cert.expire'], $config['openssl.config']);

openssl_pkey_export_to_file($privateKey, $config['files']['privateKey']);
openssl_x509_export_to_file($x509, $config['files']['certificate']);