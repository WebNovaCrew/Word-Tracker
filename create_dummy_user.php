<?php
require_once __DIR__ . '/config.php';
$database = new Database();
$db = $database->getConnection();

try {
    $username = 'testuser';
    $email = 'test@example.com';
    // Check if user exists
    $check = $db->prepare("SELECT id FROM users WHERE username = :u OR email = :e");
    $check->bindParam(':u', $username);
    $check->bindParam(':e', $email);
    $check->execute();

    if ($check->rowCount() == 0) {
        $pass = password_hash('password', PASSWORD_BCRYPT);
        $stmt = $db->prepare("INSERT INTO users (username, email, password_hash) VALUES (:u, :e, :p)");
        $stmt->bindParam(':u', $username);
        $stmt->bindParam(':e', $email);
        $stmt->bindParam(':p', $pass);
        $stmt->execute();
        echo "User 'testuser' created with ID: " . $db->lastInsertId();
    } else {
        $user = $check->fetch(PDO::FETCH_ASSOC);
        echo "User already exists with ID: " . $user['id'];
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>