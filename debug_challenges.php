<?php
require_once 'config.php';
$database = new Database();
$db = $database->getConnection();

echo "<h2>Group Challenges Table</h2>";
try {
    $stmt = $db->query("SELECT * FROM group_challenges");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (empty($rows)) {
        echo "No challenges found.<br>";
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

echo "<h2>Participants Table</h2>";
try {
    $stmt = $db->query("SELECT * FROM challenge_participants");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (empty($rows)) {
        echo "No participants found.<br>";
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