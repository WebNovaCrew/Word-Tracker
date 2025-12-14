<?php
// backend-php/test_db_connection.php
require_once 'config/database.php';

echo "<h1>System Check</h1>";
echo "PHP Version: " . phpversion() . "<br>";
echo "Server Software: " . $_SERVER['SERVER_SOFTWARE'] . "<br>";

echo "<h2>Database Connection Check</h2>";
try {
    $database = new Database();
    $conn = $database->getConnection();
    if ($conn) {
        echo "<h3 style='color:green'>✅ Database Connected Successfully!</h3>";
    } else {
        echo "<h3 style='color:red'>❌ Connection returned null (Check logs)</h3>";
    }
} catch (Exception $e) {
    echo "<h3 style='color:red'>❌ Database Error: " . $e->getMessage() . "</h3>";
    echo "<p>Ensure your XAMPP MySQL is running and the database 'word_tracker' exists.</p>";
}
?>