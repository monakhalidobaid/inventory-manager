<?php
require_once '../../config/db.php';
require_once '../../config/secure_page.php';
header('Content-Type: application/json; charset=utf-8');

// Get the last 5 reports ordered by most recent
$sql = "SELECT report_id, report_name, report_type, generated_by, 
        DATE_FORMAT(generation_date, '%Y-%m-%d %H:%i:%s') as generation_date
        FROM reports_history 
        ORDER BY generation_date DESC 
        LIMIT 5";

$result = $conn->query($sql);

if (!$result) {
    echo json_encode(["status" => "error", "message" => "Query failed: " . $conn->error]);
    exit;
}

$reports = [];
while ($row = $result->fetch_assoc()) {
    $reports[] = $row;
}

echo json_encode([
    "status" => "success",
    "count" => count($reports),
    "reports" => $reports
]);

$conn->close();
?>