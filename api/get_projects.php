<?php
// backend-php/api/get_projects.php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

require_once __DIR__ . '/../config.php';

try {
    $database = new Database();
    $conn = $database->getConnection();

    $user_id = isset($_GET['user_id']) ? $_GET['user_id'] : 1;

    // Log for debugging
    error_log("Fetching projects for User ID: " . $user_id);

    // Verify table exists first (lightweight check) to avoid fatal errors if setup failed
// $check = $conn->query("SHOW TABLES LIKE 'projects'");
// if ($check->rowCount() == 0) { ... }
// Actually, let's just query. If it fails, catch it.

    $page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
    $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 10;
    $offset = ($page - 1) * $limit;

    // Base query wrapper for counting
    // We need to count the total results of the UNION
    $countQuery = "
        SELECT COUNT(*) as total FROM (
            SELECT p.id
            FROM projects p 
            WHERE p.user_id = :uid
            UNION
            SELECT p.id
            FROM projects p 
            JOIN project_shares ps ON p.id = ps.project_id 
            WHERE ps.user_id = :uid2
        ) as combined_table
    ";

    $stmtCount = $conn->prepare($countQuery);
    $stmtCount->bindParam(':uid', $user_id);
    $stmtCount->bindParam(':uid2', $user_id);
    $stmtCount->execute();
    $total_items = $stmtCount->fetch(PDO::FETCH_ASSOC)['total'];
    $total_pages = ceil($total_items / $limit);

    // Main query with Limit
    $query = "
        SELECT p.*, 'owner' as role 
        FROM projects p 
        WHERE p.user_id = :uid
        UNION
        SELECT p.*, ps.permission_level as role 
        FROM projects p 
        JOIN project_shares ps ON p.id = ps.project_id 
        WHERE ps.user_id = :uid2
        ORDER BY created_at DESC
        LIMIT :limit OFFSET :offset
    ";

    $stmt = $conn->prepare($query);
    $stmt->bindParam(':uid', $user_id);
    $stmt->bindParam(':uid2', $user_id);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

    if ($stmt->execute()) {
        $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode([
            'records' => $projects,
            'pagination' => [
                'current_page' => $page,
                'total_pages' => $total_pages,
                'total_items' => $total_items,
                'items_per_page' => $limit
            ]
        ]);
    } else {
        throw new Exception("Query execution failed");
    }

} catch (Exception $e) {
    error_log("Get Projects Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage(), 'records' => []]);
}
?>