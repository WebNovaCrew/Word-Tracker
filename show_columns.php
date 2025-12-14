<?php
$pdo = new PDO('mysql:host=localhost;dbname=word_tracker', 'root', '');
$stmt = $pdo->query('SHOW COLUMNS FROM plans');
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo $row['Field'] . ' (' . $row['Type'] . ")\n";
}
?>