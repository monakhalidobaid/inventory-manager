<?php
header('Content-Type: application/json');
include_once '../../config/db.php';
include_once '../../config/secure_page.php';

$q = isset($_GET['q']) ? trim($_GET['q']) : '';
$page  = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 5;
$offset = ($page - 1) * $limit;

try {
    if ($q !== '') {
        $stmt = $conn->prepare("
            SELECT e.emp_id,e.emp_name, e.emp_no, e.status, e.hire_date,
                   e.contract_end_date, e.dept_id, d.dept_name
            FROM employees e
            LEFT JOIN departments d ON e.dept_id = d.dept_id
            WHERE e.emp_name LIKE ? 
               OR e.emp_no LIKE ? 
               OR d.dept_name LIKE ?
            ORDER BY e.emp_id DESC
            LIMIT ? OFFSET ?
        ");
        $searchTerm = "%$q%";
        $stmt->bind_param("sssii", $searchTerm, $searchTerm, $searchTerm, $limit, $offset);

        $countStmt = $conn->prepare("
            SELECT COUNT(*) as total
            FROM employees e
            LEFT JOIN departments d ON e.dept_id = d.dept_id
            WHERE e.emp_name LIKE ? 
               OR e.emp_no LIKE ? 
               OR d.dept_name LIKE ?
        ");
        $countStmt->bind_param("sss", $searchTerm, $searchTerm, $searchTerm);

    } else {
        $stmt = $conn->prepare("
            SELECT e.emp_id, e.emp_name, e.emp_no, e.status, e.hire_date,
                   e.contract_end_date, e.dept_id, d.dept_name
            FROM employees e
            LEFT JOIN departments d ON e.dept_id = d.dept_id
            ORDER BY e.emp_id DESC
            LIMIT ? OFFSET ?
        ");
        $stmt->bind_param("ii", $limit, $offset);

        $countStmt = $conn->prepare("SELECT COUNT(*) as total FROM employees");
    }

    $stmt->execute();
    $result = $stmt->get_result();
    $employees = [];
    while ($row = $result->fetch_assoc()) {
        $employees[] = $row;
    }

    $countStmt->execute();
    $countRes = $countStmt->get_result();
    $total = $countRes->fetch_assoc()['total'];

    echo json_encode([
        'success' => true,
        'employees' => $employees,
        'total' => $total,
        'page' => $page,
        'limit' => $limit
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}
