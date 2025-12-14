<?php
header("Content-Type: application/json");
require_once 'config.php';
$database = new Database();
$db = $database->getConnection();
$stmt = $db->query("SELECT * FROM plans");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($rows, JSON_PRETTY_PRINT);
?>