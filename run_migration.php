<?php
// Database Migration Script for Railway
// This script creates all necessary database tables

include_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

if (!$db) {
    echo "Failed to connect to database.\n";
    exit(1);
}

echo "Starting migration...\n\n";

// Function to run a SQL file
function runSqlFile($db, $filePath)
{
    if (!file_exists($filePath)) {
        echo "❌ File not found: $filePath\n";
        return false;
    }

    echo "📄 Processing: $filePath\n";
    $sql = file_get_contents($filePath);

    // Remove CREATE DATABASE and USE statements (Railway provides the database)
    $sql = preg_replace('/CREATE DATABASE IF NOT EXISTS.*?;/i', '', $sql);
    $sql = preg_replace('/USE .*?;/i', '', $sql);

    // Split into individual statements
    $statements = array_filter(
        array_map('trim', preg_split('/;[\r\n]+/', $sql)),
        function ($stmt) {
            return !empty($stmt) && !preg_match('/^--/', $stmt);
        }
    );

    $success = true;
    $created = 0;
    $skipped = 0;

    foreach ($statements as $statement) {
        if (empty(trim($statement)))
            continue;

        try {
            $db->exec($statement . ';');
            $created++;

            // Extract table name for better output
            if (preg_match('/CREATE TABLE IF NOT EXISTS (\w+)/i', $statement, $matches)) {
                echo "  ✅ Created table: {$matches[1]}\n";
            }
        } catch (PDOException $e) {
            // Ignore "table already exists" errors
            if (strpos($e->getMessage(), 'already exists') !== false) {
                $skipped++;
                if (preg_match('/CREATE TABLE IF NOT EXISTS (\w+)/i', $statement, $matches)) {
                    echo "  ℹ️  Table already exists: {$matches[1]}\n";
                }
            } else {
                echo "  ❌ Error: " . $e->getMessage() . "\n";
                $success = false;
            }
        }
    }

    echo "  📊 Created: $created, Skipped: $skipped\n\n";
    return $success;
}

// Run migrations
echo "=== Database Migration ===\n\n";

// 1. Run main schema
if (runSqlFile($db, 'migrations/schema.sql')) {
    echo "✅ Main schema processed successfully\n\n";
} else {
    echo "⚠️  Main schema had some errors\n\n";
}

// 2. Run additional migrations
if (file_exists('migrations/001_add_plan_fields.sql')) {
    if (runSqlFile($db, 'migrations/001_add_plan_fields.sql')) {
        echo "✅ Additional migrations processed successfully\n\n";
    } else {
        echo "⚠️  Additional migrations had some errors\n\n";
    }
}

// Verify tables were created
echo "=== Verification ===\n\n";
try {
    $stmt = $db->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

    echo "📋 Tables in database:\n";
    foreach ($tables as $table) {
        $countStmt = $db->query("SELECT COUNT(*) FROM `$table`");
        $count = $countStmt->fetchColumn();
        echo "  • $table ($count rows)\n";
    }

    echo "\n";
    echo "=== Summary ===\n";
    echo "✅ Total tables: " . count($tables) . "\n";
    echo "✅ Migration completed successfully!\n\n";
    echo "🎉 Your database is ready to use!\n";

} catch (PDOException $e) {
    echo "❌ Verification failed: " . $e->getMessage() . "\n";
}
?>