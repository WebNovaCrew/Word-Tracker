<?php
require_once 'config.php';
$database = new Database();
$db = $database->getConnection();
$stmt = $db->query("SELECT * FROM plans WHERE id = 18");
$plan = $stmt->fetch(PDO::FETCH_ASSOC);
print_r($plan);
?>