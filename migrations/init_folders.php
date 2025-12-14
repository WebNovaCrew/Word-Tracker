<?php
// backend-php/migrations/init_folders.php
$baseDir = realpath(__DIR__ . '/..');
include_once $baseDir . '/config.php';
// include_once $baseDir . '/config/database.php';

$database = new Database();
$db = $database->getConnection();

echo "Running migration...\n";

// 1. Create folders table
$sql = "CREATE TABLE IF NOT EXISTS folders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

try {
    $db->exec($sql);
    echo "Folders table created (or exists).\n";
} catch (PDOException $e) {
    die("Error creating table: " . $e->getMessage());
}

// 2. Add folder_id to plans
$colSql = "SHOW COLUMNS FROM plans LIKE 'folder_id'";
$stmt = $db->query($colSql);
if ($stmt->rowCount() == 0) {
    echo "Adding folder_id to plans...\n";
    $alterSql = "ALTER TABLE plans ADD COLUMN folder_id INT NULL DEFAULT NULL";
    try {
        $db->exec($alterSql);
        $db->exec("ALTER TABLE plans ADD CONSTRAINT fk_plan_folder FOREIGN KEY (folder_id) REFERENCES folders(id) ON DELETE SET NULL");
        echo "Column added.\n";
    } catch (PDOException $e) {
        die("Error altering table: " . $e->getMessage());
    }
} else {
    echo "folder_id already exists in plans.\n";
}

echo "Migration complete.";
?>