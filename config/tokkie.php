<?php

$serviceName = 'Tokkie Auth';
$issuer = 'T O K K I E';
$emailAddress = 'dont@me.bro';
$storage = realpath(__DIR__ . '/../storage');
$bundle = $storage . '/bundle';

return [
    'service' => $serviceName,
    'docker' => [
        # Token dispenser path, should point to docker.php in public
        'token-dispenser' => 'http://localhost:5001/docker.php',
        'service' => $serviceName,
        'issuer' => $issuer,
        'users' => [
            'example' => [
                # password_hash hash, for password
                'pass' => password_hash('example password', PASSWORD_DEFAULT),
                # function or boolean, function should return array with allowed actions, gets repo and actions as arguments
                'allow' => true,
            ]
        ],
        # Unauthenticated user, allow function (or boolean)
        'user.guest' => function ($repo, $actions) {
            return ['pull'];
        }
    ],
    'openssl.config' => [
        'digest_alg' => 'SHA512',
        'private_key_bits' => 4096,
        'private_key_type' => OPENSSL_KEYTYPE_RSA,
    ],
    'cert.expire' => 3650, # 10 (badly defined) years
    'cert.dn' => [
        'commonName' => $serviceName,
        'emailAddress' => $emailAddress
    ],
    'files' => [
        # PROTECC
        'privateKey' => $storage . '/certificate.key',
        'certificate' => $bundle . '/tokkie.crt'
    ],
    # Only RS256, RS384 and RS512 supported
    'jwt.alg' => 'RS512',
];