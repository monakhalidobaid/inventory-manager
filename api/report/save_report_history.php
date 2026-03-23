<?php
require_once '../../config/db.php';
require_once '../../config/secure_page.php';

header('Content-Type: application/json; charset=utf-8');

// Get JSON input
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data) {
    echo json_encode(["status" => "error", "message" => "Invalid JSON data."]);
    exit;
}

$reportName = $data['report_name'] ?? '';
$reportType = $data['report_type'] ?? '';
$generatedBy = $_SESSION['user_name'] ?? $_SESSION['user_id'] ?? 'Unknown';

// Validate inputs
if (empty($reportName) || empty($reportType) || empty($generatedBy)) {
    echo json_encode(["status" => "error", "message" => "Missing required fields."]);
    exit;
}

// Prepare SQL statement
$stmt = $conn->prepare("INSERT INTO reports_history (report_name, report_type, generated_by) VALUES (?, ?, ?)");

if (!$stmt) {
    echo json_encode(["status" => "error", "message" => "Database error: " . $conn->error]);
    exit;
}

$stmt->bind_param("sss", $reportName, $reportType, $generatedBy);

if ($stmt->execute()) {
    echo json_encode([
        "status" => "success",
        "message" => "Report saved to history successfully.",
        "report_id" => $stmt->insert_id
    ]);
} else {
    echo json_encode(["status" => "error", "message" => "Failed to save report: " . $stmt->error]);
}

$stmt->close();
$conn->close();
?>