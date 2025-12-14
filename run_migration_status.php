<?php
// backend-php/run_migration_status.php
require_once 'config.php';

try {
    $database = new Database();
    $db = $database->getConnection();

    $sql = file_get_contents('../database/migration_add_status.sql');

    // Split into individual queries if necessary, but simple ALTERs can often run together or one by one.
    // PDO doesn't always like multiple statements in one execute call depending on config.
    // Let's split by semicolon.

    $queries = explode(';', $sql);

    foreach ($queries as $query) {
        $query = trim($query);
        if (!empty($query)) {
            $db->exec($query);
            echo "Executed: " . substr($query, 0, 50) . "...\n";
        }
    }

    echo "Migration completed successfully.\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>