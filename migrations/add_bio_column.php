<?php
// Add bio column to users table if it doesn't exist
require_once '../config.php';

try {
    $database = new Database();
    $conn = $database->getConnection();

    // Check if bio column exists
    $stmt = $conn->query("SHOW COLUMNS FROM users LIKE 'bio'");
    $exists = $stmt->fetch();

    if (!$exists) {
        // Add bio column
        $conn->exec("ALTER TABLE users ADD COLUMN bio TEXT DEFAULT NULL AFTER email");
        echo "Bio column added successfully!\n";
    } else {
        echo "Bio column already exists.\n";
    }

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>