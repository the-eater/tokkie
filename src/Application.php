<?php

namespace Eater\Tokkie;


use Base32\Base32;

class Application
{
    public $config;

    private $algToHash = [
        'RS512' => OPENSSL_ALGO_SHA512,
        'RS384' => OPENSSL_ALGO_SHA384,
        'RS256' => OPENSSL_ALGO_SHA256,
    ];

    private $privateKey;

    public function __construct($config)
    {
        $this->config = $config;
    }

    public function createToken($payload)
    {
        $token = $this->shittyBase64encode(json_encode([
            'alg' => $this->config['jwt.alg'],
            'type' => 'JWT',
            'kid' => $this->getKeyId(),
        ]));

        $token .= '.' . $this->shittyBase64encode(json_encode($payload));

        return $token . '.' . $this->shittyBase64encode($this->sign($token));
    }

    public function shittyBase64encode($input)
    {
        return str_replace('=', '', strtr(base64_encode($input), '+/', '-_'));
    }

    public function shittyBase64decode($input)
    {
        $remainder = strlen($input) % 4;
        if ($remainder) {
            $padlen = 4 - $remainder;
            $input .= str_repeat('=', $padlen);
        }

        return base64_decode(strtr($input, '-_', '+/'));
    }

    public function validateToken($token)
    {
        $parts = explode('.', $token);

        if (count($parts) !== 3) {
            return false;
        }


        if (1 === $this->openssl(function() use ($parts) {
                $res = openssl_pkey_get_public($this->getPublicKey());
                $wat =  openssl_verify($parts[0] . '.' . $parts[1], $this->shittyBase64decode($parts[2]), $res, $this->algToHash[$this->config['jwt.alg']]);
                return $wat;
            })) {
            return json_decode($this->shittyBase64decode($parts[1]), true);
        }

        return false;
    }

    public function getPrivateKey()
    {
        if ($this->privateKey === null) {
            $this->privateKey = $this->openssl(function () {
                return openssl_pkey_get_private(file_get_contents($this->config['files']['privateKey']));
            });
        }

        return $this->privateKey;
    }

    public function getPublicKey()
    {
        $details = $this->openssl(function () {
            return openssl_pkey_get_details($this->getPrivateKey());
        });

        return $details['key'];
    }

    public function getKeyId()
    {
        preg_match(':-----BEGIN[^-]+-----(?P<der>(.|\n)+)-----END:', $this->getPublicKey(), $matches);

        if (!isset($matches['der'])) {
            throw new \Exception("Can't find body of key");
        }

        $der = preg_replace(':[\s\n]+:', '', $matches['der']);
        $der = base64_decode($der);

        $truncated = substr(hash('sha256', $der, true), 0, 30);
        return implode(':', str_split(Base32::encode($truncated), 4));
    }

    public function sign($token)
    {
        if (!isset($this->algToHash[$this->config['jwt.alg']])) {
            throw new \Exception("Unimplemented or invalid JWT Algorithm: {$this->config['jwt.alg']}");
        }

        $this->openssl(function () use (&$out, $token) {
            return openssl_sign($token, $out, $this->getPrivateKey(), $this->algToHash[$this->config['jwt.alg']]);
        });

        return $out;
    }

    public function openssl($func)
    {
        while (openssl_error_string()) ;

        $ret = $func();

        $errors = [];
        while ($error = openssl_error_string()) {
            $errors[] = $error;
        }

        if ($ret === false) {
            throw new \Exception("{$func} failed: " . implode(", ", $errors));
        }

        return $ret;
    }
}