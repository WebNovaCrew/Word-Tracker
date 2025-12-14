<?php
require_once 'config.php';

$database = new Database();
$db = $database->getConnection();

$user_id = isset($_GET['user_id']) ? $_GET['user_id'] : null;

if (!$user_id) {
    http_response_code(400);
    echo json_encode(["message" => "Missing user_id parameter."]);
    exit();
}

$query = "SELECT * FROM plans WHERE user_id = :user_id ORDER BY created_at DESC";
$stmt = $db->prepare($query);
$stmt->bindParam(":user_id", $user_id);
$stmt->execute();

$plans = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $plans[] = $row;
}

http_response_code(200);
echo json_encode(["records" => $plans]);
?>