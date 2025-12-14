<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set("display_errors", 1);

// CORS Headers
if (!headers_sent()) {
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Headers: Content-Type, Authorization");
    header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
    header("Content-Type: application/json; charset=UTF-8");
}

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Database Configuration
$host = getenv('MYSQLHOST') ?: '127.0.0.1';
$database = getenv('MYSQLDATABASE') ?: 'word_tracker';
$username = getenv('MYSQLUSER') ?: 'root';
$password = getenv('MYSQLPASSWORD') ?: '';
$port = getenv('MYSQLPORT') ?: '3306';

define('DB_HOST', $host);
define('DB_NAME', $database);
define('DB_USER', $username);
define('DB_PASS', $password);
define('DB_PORT', $port);

// Database Connection Function
function getDBConnection()
{
    try {
        $conn = new PDO(
            "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4",
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]
        );
        return $conn;
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode([
            "success" => false,
            "message" => "Database connection failed: " . $e->getMessage()
        ]);
        exit();
    }
}

// Helper function to get JSON input
function getJSONInput()
{
    $json = file_get_contents("php://input");
    $data = json_decode($json, true);
    return $data ? $data : [];
}

// Helper function to send JSON response
function sendResponse($success, $message, $data = null)
{
    $response = [
        "success" => $success,
        "message" => $message
    ];
    if ($data !== null) {
        $response["data"] = $data;
    }
    echo json_encode($response);
    exit();
}
?>