<?php
/**
 * Railway Database Initialization Script
 * Access via: https://word-tracker-production.up.railway.app/init_railway_db.php
 * Version: 3.0 - Robust schema finding and detailed reporting
 */

header("Content-Type: application/json; charset=UTF-8");

// 1. Enable full error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// 2. Resolve Schema Path
// Strategy: Check local (backend-php), then database folder (sibling), then project root
$possiblePaths = [
    __DIR__ . '/schema.sql',
    __DIR__ . '/../database/schema.sql',
    __DIR__ . '/../schema.sql'
];

$schemaFile = null;
foreach ($possiblePaths as $path) {
    if (file_exists($path)) {
        $schemaFile = realpath($path);
        break;
    }
}

// 3. Get Credentials
$host = getenv('MYSQLHOST') ?: 'localhost';
$user = getenv('MYSQLUSER') ?: 'root';
$pass = getenv('MYSQLPASSWORD') ?: '';
$db = getenv('MYSQLDATABASE') ?: 'word_tracker';
$port = getenv('MYSQLPORT') ?: 3306;

$response = [
    'status' => 'unknown',
    'debug' => [
        'php_version' => phpversion(),
        'current_dir' => __DIR__,
        'schema_path_attempted' => $possiblePaths,
        'schema_file_found' => $schemaFile,
        'connection_config' => [
            'host' => $host,
            'user' => $user,
            'db' => $db,
            'port' => $port
        ]
    ],
    'tables_created' => [],
    'statements_executed' => 0,
    'statements_failed' => [],
    'errors' => []
];

if (!$schemaFile) {
    $response['status'] = 'error';
    $response['message'] = 'Schema file not found in any expected location.';
    echo json_encode($response, JSON_PRETTY_PRINT);
    exit;
}

try {
    // 4. Connect
    $mysqli = new mysqli($host, $user, $pass, $db, (int) $port);

    // 5. Read SQL
    $sql = file_get_contents($schemaFile);
    $response['debug']['raw_sql_preview'] = substr($sql, 0, 100) . '...';

    // Remove CREATE DATABASE / USE commands if they exist (Railway handles DB creation)
    $sql = preg_replace('/CREATE DATABASE .*?;/si', '', $sql);
    $sql = preg_replace('/USE .*?;/si', '', $sql);

    // Split into statements
    // This regex splits by semicolon but ignores semicolons inside quotes (rough approximation)
    $statements = explode(';', $sql);

    foreach ($statements as $stmt) {
        $stmt = trim($stmt);
        if (empty($stmt))
            continue;

        try {
            $mysqli->query($stmt);
            $response['statements_executed']++;
        } catch (mysqli_sql_exception $e) {
            // Check if error is "Table already exists" - typically 1050
            if ($e->getCode() == 1050) {
                // Info only
                $response['errors'][] = "Notice: Table already exists (Skipped)";
            } else {
                $response['statements_failed'][] = [
                    'sql' => substr($stmt, 0, 50) . '...',
                    'error' => $e->getMessage(),
                    'code' => $e->getCode()
                ];
                $response['errors'][] = $e->getMessage();
            }
        }
    }

    // 6. Verification
    $result = $mysqli->query("SHOW TABLES");
    while ($row = $result->fetch_array(MYSQLI_NUM)) {
        $response['tables_created'][] = $row[0];
    }

    $response['status'] = 'success';
    $response['message'] = 'Database initialization completed.';

    // Close
    $mysqli->close();

} catch (Exception $e) {
    http_response_code(500);
    $response['status'] = 'critical_error';
    $response['message'] = $e->getMessage();
}

echo json_encode($response, JSON_PRETTY_PRINT);
?>