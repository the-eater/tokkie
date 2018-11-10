<?php


namespace Eater\Tokkie;


class Docker
{
    private $application;
    private $config;
    private $user = null;
    private $guest = true;
    private $userHandle = false;

    public function __construct(Application $application)
    {
        $this->application = $application;
        $this->config = $application->config['docker'];
        $this->user = $this->config['user.guest'];
    }

    public function handle()
    {
        if (isset($_SERVER['PHP_AUTH_USER']) && !$this->authenticate($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW'])) {
            http_response_code(401);
            return;
        }

        if (isset($_POST['grant_type'])) {
            switch ($_POST['grant_type']) {
                case 'password':
                    if (!$this->authenticate($_POST['username'], $_POST['password'])) {
                        http_response_code(401);
                        return;
                    }
                    break;
                case 'refresh_token':
                    $payload = $this->application->validateToken($_POST['refresh_token']);
                    if ($payload === false || !isset($payload['user']) || !isset($this->config['users'][$payload['user']])) {
                        http_response_code(401);
                        return;
                    }

                    $this->userHandle = $payload['user'];
                    $this->user = $this->config['users'][$payload['user']];
                    $this->guest = false;
                    break;
            }
        }

        $scopes = trim($_GET['scope'] ?? ($_POST['scope'] ?? ''));

        if ($scopes === "") {
            $scopes = [];
        } else {
            $scopes = preg_split(':\s+:', $scopes);
        }

        $body = [
            'access_token' => $this->getToken($scopes),
        ];

        if (isset($_GET['offline_token']) && !$this->guest) {
            $body['refresh_token'] = $this->buildToken([
                'user' => $this->userHandle
            ]);
        }

        header('Content-Type: application/json');
        echo json_encode($body);
    }

    public function testAllow($repo, $actions) {
        if ($this->user['allow'] === true) {
            return $actions;
        }

        if (is_callable($this->user['allow'])) {
            return $this->user['allow']($repo, $actions);
        }

        return [];
    }

    public function buildToken($payload, $expire = 3600) {
        $now = time();

        $payload = array_merge($payload, [
            'iss' => $this->config['issuer'],
            'nbf' => $now,
            'iat' => $now,
            'aud' => $this->config['service'],
        ]);

        if ($expire !== false) {
            $payload['exp'] = $now + $expire;
        }

        return $this->application->createToken($payload);
    }

    public function getToken($scopes, $expire = 3600) {
        $access = [];

        foreach ($scopes as $scope) {
            $scopeParts = explode(':', $scope);

            if (count($scopeParts) !== 3) {
                continue;
            }

            $access[] = [
                'type' => $scopeParts[0],
                'name' => $scopeParts[1],
                'actions' => $this->testAllow($scopeParts[1], explode(',', $scopeParts[2])),
            ];
        }

        error_log('Allowed scopes: ' . print_r($access, true));

        return $this->buildToken([
            'access' => $access
        ]);
    }

    public function authenticate($username, $password)
    {
        if (!isset($this->config['users'][$username]['pass']) || !password_verify($password, $this->config['users'][$username]['pass'])) {
            return false;
        }

        $this->userHandle = $username;
        $this->guest = false;
        $this->user = $this->config['users'][$username];
        return true;
    }
}