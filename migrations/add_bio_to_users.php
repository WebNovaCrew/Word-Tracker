<?php
require_once __DIR__ . '/../config.php';

$database = new Database();
$conn = $database->getConnection();

try {
    // Check if column exists
    $check = "SHOW COLUMNS FROM users LIKE 'bio'";
    $stmt = $conn->query($check);

    if ($stmt->rowCount() == 0) {
        $sql = "ALTER TABLE users ADD COLUMN bio TEXT AFTER email";
        $conn->exec($sql);
        echo "Column 'bio' added successfully.\n";
    } else {
        echo "Column 'bio' already exists.\n";
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>