<?php
// backend-php/config/database.php

class Database
{
    // Database credentials - Railway or XAMPP
    private $host;
    private $db_name;
    private $username;
    private $password;
    private $port;
    public $conn;

    public function __construct()
    {
        if (getenv('MYSQLHOST')) {
            $this->host = getenv('MYSQLHOST');
            $this->db_name = getenv('MYSQLDATABASE');
            $this->username = getenv('MYSQLUSER');
            $this->password = getenv('MYSQLPASSWORD');
            $this->port = getenv('MYSQLPORT') ?: '3306';
            return;
        }

        $url = getenv('MYSQL_URL') ?: (getenv('MYSQL_PRIVATE_URL') ?: (getenv('MYSQL_PUBLIC_URL') ?: getenv('DATABASE_URL')));
        if ($url) {
            $parts = parse_url($url);
            if ($parts !== false && isset($parts['host'])) {
                $this->host = $parts['host'];
                $this->port = isset($parts['port']) ? (string) $parts['port'] : '3306';
                $this->username = isset($parts['user']) ? urldecode($parts['user']) : '';
                $this->password = isset($parts['pass']) ? urldecode($parts['pass']) : '';
                $this->db_name = isset($parts['path']) ? ltrim($parts['path'], '/') : '';

                if (!$this->db_name) {
                    $this->db_name = getenv('MYSQLDATABASE') ?: 'word_tracker';
                }
                return;
            }
        }

        $this->host = "127.0.0.1";
        $this->db_name = "word_tracker";
        $this->username = "root";
        $this->password = "";
        $this->port = "3306";
    }

    public function getConnection()
    {
        $this->conn = null;

        try {
            $dsn = "mysql:host=" . $this->host . ";port=" . $this->port . ";dbname=" . $this->db_name . ";charset=utf8mb4";

            $this->conn = new PDO($dsn, $this->username, $this->password);

            // Error Handling
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $this->conn->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

        } catch (PDOException $exception) {
            // Return JSON error ensuring frontend can parse it
            header("Content-Type: application/json");
            http_response_code(500);
            echo json_encode([
                "success" => false,
                "message" => "Database Connection Error: " . $exception->getMessage()
            ]);
            exit;
        }

        return $this->conn;
    }
}
?>