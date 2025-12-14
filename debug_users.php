<?php
require_once __DIR__ . '/config.php';
$database = new Database();
$db = $database->getConnection();

echo "<h2>Users (Top 10)</h2>";
try {
    $stmt = $db->query("SELECT id, username, email FROM users LIMIT 10");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (empty($rows)) {
        echo "No users found.<br>";
    } else {
        echo "<table border='1'><tr><th>ID</th><th>Username</th><th>Email</th></tr>";
        foreach ($rows as $row) {
            echo "<tr><td>{$row['id']}</td><td>{$row['username']}</td><td>{$row['email']}</td></tr>";
        }
        echo "</table>";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "<br>";
}
?>