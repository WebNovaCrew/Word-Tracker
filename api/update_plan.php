<?php
// backend-php/api/update_plan.php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, PUT, OPTIONS");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Logging Function
function logDebug($message)
{
    error_log('[UpdatePlan] ' . $message);
}

// Handle Preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$baseDir = realpath(__DIR__ . '/..');
include_once $baseDir . '/config.php';
include_once $baseDir . '/models/Plan.php';

try {
    $database = new Database();
    $db = $database->getConnection();

    // Get input
    $rawInput = file_get_contents("php://input");
    logDebug("Raw Input: " . $rawInput);

    $data = json_decode($rawInput);

    if (empty($data)) {
        throw new Exception("No data provided.");
    }

    if (empty($data->id)) {
        throw new Exception("Plan ID is required.");
    }

    $planId = $data->id;

    // Fetch existing plan to see what changed
    $checkQuery = "SELECT * FROM plans WHERE id = :id LIMIT 1";
    $checkStmt = $db->prepare($checkQuery);
    $checkStmt->bindParam(":id", $planId);
    $checkStmt->execute();

    if ($checkStmt->rowCount() == 0) {
        http_response_code(404);
        throw new Exception("Plan not found.");
    }

    $existingPlan = $checkStmt->fetch(PDO::FETCH_ASSOC);

    // Prepare Update Fields
    // We only update fields that are present in the request
    $updateFields = [];
    $params = [':id' => $planId];

    $allowedFields = [
        'name',
        'content_type',
        'activity_type',
        'goal_amount',
        'start_date',
        'end_date',
        'strategy',
        'intensity',
        'folder_id',
        'color_code',
        'is_archived',
        'status'
    ];

    foreach ($allowedFields as $field) {
        if (isset($data->$field)) {
            $updateFields[] = "$field = :$field";
            $params[":$field"] = $data->$field;
        }
    }

    // Always update 'updated_at'
    $updateFields[] = "updated_at = NOW()";

    if (empty($updateFields)) {
        // Nothing to update
        echo json_encode(["status" => "success", "message" => "No changes made."]);
        exit;
    }

    $sql = "UPDATE plans SET " . implode(", ", $updateFields) . " WHERE id = :id";
    logDebug("Update SQL: " . $sql);

    $stmt = $db->prepare($sql);

    if (!$stmt->execute($params)) {
        $errorInfo = $stmt->errorInfo();
        throw new Exception("Database error: " . $errorInfo[2]);
    }

    // Recalculate Logic: If dates or goal_amount changed, we might need to regenerate plan_days
    // This is a simplified version: If critical fields change, we SHOULD wipe and recreate days or adjust them.
    // For now, let's assume if dates/goal change, we do a basic regeneration to keep it consistent.

    $shouldRecalculate = (
        (isset($data->start_date) && $data->start_date != $existingPlan['start_date']) ||
        (isset($data->end_date) && $data->end_date != $existingPlan['end_date']) ||
        (isset($data->goal_amount) && $data->goal_amount != $existingPlan['goal_amount'])
    );

    if ($shouldRecalculate) {
        logDebug("Recalculating plan schedule...");

        // Use new values or fallback to existing
        $newStart = $data->start_date ?? $existingPlan['start_date'];
        $newEnd = $data->end_date ?? $existingPlan['end_date'];
        $newGoal = $data->goal_amount ?? $existingPlan['goal_amount'];

        // 1. Delete future un-logged days (or all days - safer for now to reset, but keep logs if complex. 
        // Simplest strategy for 'update plan': Wipe and recreate days, BUT preservation of 'logged' progress is hard if dates shift completely.
        // Assuming simple overwrite for now as is typical in MVP.
        // TODO: In a production app, we would try to map existing logged counts to new dates.

        $deleteDays = "DELETE FROM plan_days WHERE plan_id = :id";
        $delStmt = $db->prepare($deleteDays);
        $delStmt->bindParam(':id', $planId);
        $delStmt->execute();

        // 2. Generate new days
        $start = new DateTime($newStart);
        $end = new DateTime($newEnd);
        $interval = new DateInterval('P1D');
        $period = new DatePeriod($start, $interval, $end->modify('+1 day'));

        $daysCount = iterator_count($period);
        $dailyTarget = ($daysCount > 0) ? ceil($newGoal / $daysCount) : $newGoal;

        $insertDay = "INSERT INTO plan_days (plan_id, date, target, logged) VALUES (:plan_id, :date, :target, 0)";
        $insStmt = $db->prepare($insertDay);

        foreach ($period as $dt) {
            $currentDate = $dt->format("Y-m-d");
            $insStmt->bindParam(":plan_id", $planId);
            $insStmt->bindParam(":date", $currentDate);
            $insStmt->bindParam(":target", $dailyTarget);
            $insStmt->execute();
        }
        logDebug("Schedule recalculated.");
    }

    http_response_code(200);
    echo json_encode([
        "status" => "success",
        "message" => "Plan updated successfully.",
        "data" => array_merge($existingPlan, (array) $data)
    ]);

} catch (Exception $e) {
    logDebug("Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);
}
?>