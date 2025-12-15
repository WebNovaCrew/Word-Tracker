<?php
try {
    $host = getenv('MYSQLHOST');
    $port = getenv('MYSQLPORT');
    $db = getenv('MYSQLDATABASE');
    $user = getenv('MYSQLUSER');
    $pass = getenv('MYSQLPASSWORD');

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
        "status" => "ok"
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "db" => "failed",
        "error" => $e->getMessage()
    ]);
}
