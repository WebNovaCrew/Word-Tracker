<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== DIRECT DATABASE CONNECTION TEST ===\n\n";

// Test connection parameters
$host = 'localhost';
$dbname = 'word_tracker';
$username = 'root';
$password = '';

echo "Attempting to connect to:\n";
echo "Host: $host\n";
echo "Database: $dbname\n";
echo "User: $username\n\n";

try {
    $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";
    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "✓ Database connection successful!\n\n";

    // Test insert
    echo "Testing INSERT...\n";
    $stmt = $pdo->prepare("INSERT INTO plans (user_id, name, content_type, activity_type, start_date, end_date, goal_amount, strategy, intensity) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");

    $result = $stmt->execute([
        1,
        'Test Plan',
        'Novel',
        'Writing',
        '2025-12-10',
        '2025-12-31',
        50000,
        'steady',
        'average'
    ]);

    if ($result) {
        $planId = $pdo->lastInsertId();
        echo "✓ Plan inserted successfully! ID: $planId\n";

        // Delete test record
        $pdo->exec("DELETE FROM plans WHERE id = $planId");
        echo "✓ Test plan deleted\n";
    }

    echo "\n=== ALL TESTS PASSED ===\n";

} catch (PDOException $e) {
    echo "✗ ERROR: " . $e->getMessage() . "\n";
    echo "Error Code: " . $e->getCode() . "\n";
}
?>