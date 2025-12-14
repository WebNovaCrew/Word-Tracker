<?php
// backend-php/debug_calendar_data.php
require_once 'config.php';

$database = new Database();
$db = $database->getConnection();

echo "--- Users ---\n";
$stmt = $db->query("SELECT id, username FROM users LIMIT 5");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
print_r($users);

if (count($users) > 0) {
    $userId = $users[0]['id'];
    echo "\n--- Plans for User ID $userId ---\n";
    $stmt = $db->prepare("SELECT id, name, start_date, end_date FROM plans WHERE user_id = ?");
    $stmt->execute([$userId]);
    $plans = $stmt->fetchAll(PDO::FETCH_ASSOC);
    print_r($plans);

    if (count($plans) > 0) {
        $planId = $plans[0]['id'];
        echo "\n--- Plan Days for Plan ID $planId ---\n";
        $stmt = $db->prepare("SELECT * FROM plan_days WHERE plan_id = ? LIMIT 5");
        $stmt->execute([$planId]);
        print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

        echo "\n--- Testing get_plan_days Query for User ID $userId ---\n";
        $query = "
            SELECT 
                pd.date, 
                SUM(pd.target) as total_target
            FROM plan_days pd
            JOIN plans p ON pd.plan_id = p.id
            WHERE p.user_id = ?
            GROUP BY pd.date
            LIMIT 5
        ";
        $stmt = $db->prepare($query);
        $stmt->execute([$userId]);
        print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
    } else {
        echo "No plans found for this user.\n";
    }
} else {
    echo "No users found.\n";
}
?>