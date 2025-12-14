<?php
$baseDir = realpath(__DIR__ . '/..');
include_once $baseDir . '/config.php';

$database = new Database();
$db = $database->getConnection();

echo "Adding color_code to plans...\n";
try {
    $sql = "ALTER TABLE plans ADD COLUMN color_code VARCHAR(7) DEFAULT '#3b82f6'";
    $db->exec($sql);
    echo "Column added.\n";
} catch (PDOException $e) {
    // Check for "Duplicate column" error (code 42S21 or generic message)
    echo "Notice/Error (might exist): " . $e->getMessage() . "\n";
}
?>