<?php
// CORS Headers
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Content-Type: application/json; charset=UTF-8");


// Handle preflight
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Database connection
class Database
{
    private $host;
    private $db_name;
    private $username;
    private $password;
    private $port;
    public $conn;

    public function __construct()
    {
        // Database Configuration
        $this->host = getenv('MYSQLHOST') ?: '127.0.0.1';
        $this->port = getenv('MYSQLPORT') ?: '3306';
        $this->username = getenv('MYSQLUSER') ?: 'root';
        $this->password = getenv('MYSQLPASSWORD') ?: '';
        $this->db_name = getenv('MYSQLDATABASE') ?: 'word_tracker';
    }

    public function getConnection()
    {
        $this->conn = null;
        try {
            $dsn = "mysql:host=" . $this->host . ";port=" . $this->port . ";dbname=" . $this->db_name . ";charset=utf8mb4";
            $this->conn = new PDO($dsn, $this->username, $this->password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch (PDOException $exception) {
            // Return JSON error with connection details
            http_response_code(500);
            echo json_encode([
                "status" => "error",
                "message" => "Connection failed: " . $exception->getMessage(),
                "debug" => [
                    "host" => $this->host,
                    "port" => $this->port,
                    "database" => $this->db_name,
                    "user" => $this->username
                ]
            ]);
            exit();
        }
        return $this->conn;
    }
}