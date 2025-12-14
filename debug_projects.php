<?php
require_once __DIR__ . '/config.php';
$database = new Database();
$db = $database->getConnection();

echo "<h2>Projects Table</h2>";
try {
    $stmt = $db->query("SELECT * FROM projects");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (empty($rows)) {
        echo "No projects found in DB.<br>";
    } else {
        echo "<table border='1'><tr>";
        foreach (array_keys($rows[0]) as $key)
            echo "<th>$key</th>";
        echo "</tr>";
        foreach ($rows as $row) {
            echo "<tr>";
            foreach ($row as $val)
                echo "<td>$val</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "<br>";
}

echo "<h2>Project Shares Table</h2>";
try {
    $stmt = $db->query("SELECT * FROM project_shares");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (empty($rows)) {
        echo "No shares found.<br>";
    } else {
        echo "<table border='1'><tr>";
        foreach (array_keys($rows[0]) as $key)
            echo "<th>$key</th>";
        echo "</tr>";
        foreach ($rows as $row) {
            echo "<tr>";
            foreach ($row as $val)
                echo "<td>$val</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "<br>";
}
?>