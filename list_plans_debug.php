<?php
require_once 'config.php';
$database = new Database();
$db = $database->getConnection();
$stmt = $db->query("SELECT id, name FROM plans");
$plans = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($plans);
?>