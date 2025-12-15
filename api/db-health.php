<?php
try {
    $host = getenv('MYSQLHOST');
    $port = getenv('MYSQLPORT');
    $db = getenv('MYSQLDATABASE');
    $user = getenv('MYSQLUSER');
    $pass = getenv('MYSQLPASSWORD');

    $envDebug = [
        'MYSQLHOST' => getenv('MYSQLHOST') ? 'set' : 'missing',
        'MYSQLPORT' => getenv('MYSQLPORT') ? 'set' : 'missing',
        'MYSQLDATABASE' => getenv('MYSQLDATABASE') ? 'set' : 'missing',
        'MYSQLUSER' => getenv('MYSQLUSER') ? 'set' : 'missing',
        'MYSQLPASSWORD' => getenv('MYSQLPASSWORD') ? 'set' : 'missing',
        'MYSQL_URL' => getenv('MYSQL_URL') ? 'set' : 'missing',
        'MYSQL_PRIVATE_URL' => getenv('MYSQL_PRIVATE_URL') ? 'set' : 'missing',
        'MYSQL_PUBLIC_URL' => getenv('MYSQL_PUBLIC_URL') ? 'set' : 'missing',
        'DATABASE_URL' => getenv('DATABASE_URL') ? 'set' : 'missing'
    ];

    $source = $host ? 'MYSQLHOST' : 'default';

    if (!$host) {
        $url = getenv('MYSQL_URL') ?: (getenv('MYSQL_PRIVATE_URL') ?: (getenv('MYSQL_PUBLIC_URL') ?: getenv('DATABASE_URL')));
        if ($url) {
            $parts = parse_url($url);
            if ($parts !== false && isset($parts['host'])) {
                $host = $parts['host'];
                $port = isset($parts['port']) ? (string) $parts['port'] : null;
                $user = isset($parts['user']) ? urldecode($parts['user']) : null;
                $pass = isset($parts['pass']) ? urldecode($parts['pass']) : null;
                $db = isset($parts['path']) ? ltrim($parts['path'], '/') : null;
                $source = 'URL_ENV';
            }
        }
    }

    $pdo = new PDO(
        "mysql:host=" . ($host ?: '127.0.0.1') .
        ";port=" . ($port ?: '3306') .
        ";dbname=" . ($db ?: 'word_tracker'),
        ($user ?: 'root'),
        ($pass ?: ''),
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    echo json_encode([
        "db" => "connected",
        "status" => "ok",
        "debug" => [
            "source" => $source,
            "resolved" => [
                "host" => ($host ?: '127.0.0.1'),
                "port" => ($port ?: '3306'),
                "db" => ($db ?: 'word_tracker'),
                "user" => ($user ?: 'root')
            ],
            "env" => $envDebug
        ]
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "db" => "failed",
        "error" => $e->getMessage(),
        "debug" => [
            "source" => isset($source) ? $source : 'unknown',
            "resolved" => [
                "host" => isset($host) ? ($host ?: '127.0.0.1') : 'unknown',
                "port" => isset($port) ? ($port ?: '3306') : 'unknown',
                "db" => isset($db) ? ($db ?: 'word_tracker') : 'unknown',
                "user" => isset($user) ? ($user ?: 'root') : 'unknown'
            ],
            "env" => isset($envDebug) ? $envDebug : []
        ]
    ]);
}
