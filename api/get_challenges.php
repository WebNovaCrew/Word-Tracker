<?php
require_once __DIR__ . '/../config.php';

// Instantiate DB & Connect
$database = new Database();
$conn = $database->getConnection();

try {
    $user_id = isset($_GET['user_id']) ? $_GET['user_id'] : 0;

    $page = isset($_GET['page']) ? (int) $_GET['page'] : null;
    $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : null;

    $baseWhere = "WHERE gc.is_public = 1 
            OR EXISTS (
                SELECT 1 FROM challenge_participants cp_check 
                WHERE cp_check.challenge_id = gc.id AND cp_check.user_id = :user_id_check
            )";

    // Pagination
    $total_items = 0;
    $total_pages = 0;

    if ($page && $limit) {
        $countSql = "SELECT COUNT(*) as total FROM group_challenges gc " . $baseWhere;
        $countStmt = $conn->prepare($countSql);
        $countStmt->bindParam(':user_id_check', $user_id);
        $countStmt->execute();
        $total_items = (int) $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
        $total_pages = ceil($total_items / $limit);
    }

    $sql = "SELECT 
                gc.id,
                gc.name,
                gc.description,
                gc.goal_type,
                gc.goal_amount,
                gc.start_date,
                gc.end_date,
                gc.is_public,
                gc.created_at,
                gc.creator_id,
                COUNT(cp.id) as participants,
                MAX(CASE WHEN cp.user_id = :user_id THEN 1 ELSE 0 END) as is_joined
            FROM group_challenges gc
            LEFT JOIN challenge_participants cp ON gc.id = cp.challenge_id
            " . $baseWhere . "
            GROUP BY 
                gc.id, gc.name, gc.description, gc.goal_type, gc.goal_amount, gc.start_date, gc.end_date, gc.is_public, gc.created_at, gc.creator_id
            ORDER BY gc.created_at DESC";

    if ($page && $limit) {
        $offset = ($page - 1) * $limit;
        $sql .= " LIMIT :limit OFFSET :offset";
    }

    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->bindParam(':user_id_check', $user_id);

    if ($page && $limit) {
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
    }

    $stmt->execute();

    $challenges = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $response = [
        'success' => true,
        'challenges' => $challenges
    ];

    if ($page && $limit) {
        $response['pagination'] = [
            "total_items" => $total_items,
            "current_page" => $page,
            "total_pages" => $total_pages,
            "limit" => $limit
        ];
    }

    echo json_encode($response);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
?>