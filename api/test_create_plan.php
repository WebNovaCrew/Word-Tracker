<?php
/**
 * Test script for create_plan.php
 * Access via: http://localhost/word-tracker/backend-php/api/test_create_plan.php
 */

header("Content-Type: text/html; charset=UTF-8");

echo "<h1>Create Plan API Test</h1>";
echo "<hr>";

// Test 1: Check if create_plan.php exists
echo "<h2>Test 1: File Existence</h2>";
if (file_exists(__DIR__ . '/create_plan.php')) {
    echo "✅ create_plan.php exists<br>";
} else {
    echo "❌ create_plan.php NOT FOUND<br>";
}
echo "<hr>";

// Test 2: Check config.php
echo "<h2>Test 2: Config File</h2>";
if (file_exists(__DIR__ . '/../config.php')) {
    echo "✅ config.php exists<br>";
    include_once '../config.php';
    echo "✅ config.php loaded successfully<br>";
} else {
    echo "❌ config.php NOT FOUND<br>";
}
echo "<hr>";

// Test 3: Database Connection
echo "<h2>Test 3: Database Connection</h2>";
try {
    $database = new Database();
    $db = $database->getConnection();
    if ($db) {
        echo "✅ Database connection successful<br>";

        // Check if plans table exists
        $stmt = $db->query("SHOW TABLES LIKE 'plans'");
        if ($stmt->rowCount() > 0) {
            echo "✅ 'plans' table exists<br>";
        } else {
            echo "❌ 'plans' table NOT FOUND<br>";
        }

        // Check if plan_days table exists
        $stmt = $db->query("SHOW TABLES LIKE 'plan_days'");
        if ($stmt->rowCount() > 0) {
            echo "✅ 'plan_days' table exists<br>";
        } else {
            echo "❌ 'plan_days' table NOT FOUND<br>";
        }
    } else {
        echo "❌ Database connection failed<br>";
    }
} catch (Exception $e) {
    echo "❌ Database error: " . $e->getMessage() . "<br>";
}
echo "<hr>";

// Test 4: Test POST Request Simulation
echo "<h2>Test 4: Simulate POST Request</h2>";
echo '<form method="POST" action="create_plan.php" style="border: 1px solid #ccc; padding: 20px; max-width: 500px;">
    <h3>Create Test Plan</h3>
    <label>User ID: <input type="number" name="user_id" value="1" required></label><br><br>
    <label>Plan Name: <input type="text" name="name" value="Test Plan" required></label><br><br>
    <label>Goal Amount: <input type="number" name="goal_amount" value="50000" required></label><br><br>
    <label>Start Date: <input type="date" name="start_date" value="' . date('Y-m-d') . '" required></label><br><br>
    <label>End Date: <input type="date" name="end_date" value="' . date('Y-m-d', strtotime('+30 days')) . '" required></label><br><br>
    <label>Content Type: <input type="text" name="content_type" value="Novel"></label><br><br>
    <label>Activity Type: <input type="text" name="activity_type" value="Writing"></label><br><br>
    <label>Strategy: <select name="strategy">
        <option value="steady">Steady</option>
        <option value="rising">Rising</option>
        <option value="biting">Biting</option>
    </select></label><br><br>
    <label>Intensity: <select name="intensity">
        <option value="average">Average</option>
        <option value="low">Low</option>
        <option value="medium">Medium</option>
    </select></label><br><br>
    <button type="submit">Test Create Plan (via Form)</button>
</form>';

echo "<hr>";

// Test 5: JavaScript Fetch Test
echo "<h2>Test 5: JavaScript Fetch Test</h2>";
echo '<button onclick="testFetch()" style="padding: 10px 20px; background: #4CAF50; color: white; border: none; cursor: pointer;">Test API via Fetch</button>';
echo '<pre id="fetchResult" style="background: #f4f4f4; padding: 10px; margin-top: 10px;"></pre>';

echo '<script>
function testFetch() {
    const resultDiv = document.getElementById("fetchResult");
    resultDiv.textContent = "Testing...";
    
    const testData = {
        user_id: 1,
        name: "Test Plan from Fetch",
        goal_amount: 50000,
        start_date: "' . date('Y-m-d') . '",
        end_date: "' . date('Y-m-d', strtotime('+30 days')) . '",
        content_type: "Novel",
        activity_type: "Writing",
        strategy: "steady",
        intensity: "average"
    };
    
    fetch("create_plan.php", {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
            "Accept": "application/json"
        },
        body: JSON.stringify(testData)
    })
    .then(response => {
        resultDiv.textContent = "Response Status: " + response.status + "\n\n";
        return response.json();
    })
    .then(data => {
        resultDiv.textContent += "Response Data:\n" + JSON.stringify(data, null, 2);
    })
    .catch(error => {
        resultDiv.textContent = "❌ Fetch Error: " + error.message;
    });
}
</script>';

echo "<hr>";

// Test 6: CORS Headers Test
echo "<h2>Test 6: CORS Headers</h2>";
echo "<p>Check if CORS headers are set correctly:</p>";
echo '<button onclick="testCORS()" style="padding: 10px 20px; background: #2196F3; color: white; border: none; cursor: pointer;">Test CORS</button>';
echo '<pre id="corsResult" style="background: #f4f4f4; padding: 10px; margin-top: 10px;"></pre>';

echo '<script>
function testCORS() {
    const resultDiv = document.getElementById("corsResult");
    resultDiv.textContent = "Testing CORS...";
    
    fetch("create_plan.php", {
        method: "OPTIONS"
    })
    .then(response => {
        resultDiv.textContent = "CORS Preflight Response:\n";
        resultDiv.textContent += "Status: " + response.status + "\n";
        resultDiv.textContent += "Access-Control-Allow-Origin: " + response.headers.get("Access-Control-Allow-Origin") + "\n";
        resultDiv.textContent += "Access-Control-Allow-Methods: " + response.headers.get("Access-Control-Allow-Methods") + "\n";
        resultDiv.textContent += "Access-Control-Allow-Headers: " + response.headers.get("Access-Control-Allow-Headers") + "\n";
    })
    .catch(error => {
        resultDiv.textContent = "❌ CORS Error: " + error.message;
    });
}
</script>';

echo "<hr>";
echo "<p><strong>API Endpoint:</strong> <code>http://localhost/word-tracker/backend-php/api/create_plan.php</code></p>";
echo "<p><strong>Frontend should use:</strong> <code>this.http.post('http://localhost/word-tracker/backend-php/api/create_plan.php', payload)</code></p>";
?>