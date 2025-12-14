<?php
require_once __DIR__ . '/../config.php';

$database = new Database();
$conn = $database->getConnection();

$data = json_decode(file_get_contents("php://input"), true);

if (!$data || empty($data['id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing project ID']);
    exit;
}

try {
    // Only update name and description for now
    $query = "UPDATE projects SET name = :name, description = :desc WHERE id = :id";
    $stmt = $conn->prepare($query);

    $name = $data['name'] ?? '';
    $desc = $data['description'] ?? '';

    // Fetch old values if not provided? Or assume complete overwrite?
    // Let's assume frontend sends current values if they are editing.
    // If name is empty, don't update name? 
    // Actually, simple CRUD usually expects full payload or PATCH semantics.
    // Let's enforce name.

    if (empty($name)) {
        http_response_code(400);
        echo json_encode(['error' => 'Name cannot be empty']);
        exit;
    }

    $stmt->bindParam(':name', $name);
    $stmt->bindParam(':desc', $desc);
    $stmt->bindParam(':id', $data['id']);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Project updated']);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Update failed']);
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>