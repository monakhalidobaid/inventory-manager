<?php
header('Content-Type: application/json');
include_once '../../config/db.php';
include_once '../../config/secure_page.php';

// جلب الـ dept_id من URL
$dept_id = isset($_GET['dept_id']) ? (int)$_GET['dept_id'] : null;

if ($dept_id) {
    $stmt = $conn->prepare("
        SELECT e.emp_id, e.emp_name
        FROM employees e
        WHERE e.dept_id = ?
        ORDER BY e.emp_name ASC
    ");
    $stmt->bind_param("i", $dept_id);
} else {
    $stmt = $conn->prepare("
        SELECT e.emp_id, e.emp_name
        FROM employees e
        ORDER BY e.emp_name ASC
    ");
}

$stmt->execute();
$result = $stmt->get_result();
$employees = [];
while($row = $result->fetch_assoc()) {
    $employees[] = $row;
}

echo json_encode([
    'success' => true,
    'employees' => $employees
]);
