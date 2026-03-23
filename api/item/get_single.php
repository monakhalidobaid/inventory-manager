<?php
header('Content-Type: application/json');
include_once '../../config/db.php';
include_once '../../config/secure_page.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid item ID']);
    exit;
}

$sql = "
    SELECT
        i.item_id,
        i.item_code,
        i.type AS item_type,
        i.item_status,
        i.purchase_date,
        i.warranty_months,
        i.warranty_end,
        i.warranty_status,
        i.description,
        c.cat_name,
        d.dept_name AS item_dept_name,
        e.emp_name,
        ed.dept_name AS emp_dept_name
    FROM item i
    LEFT JOIN categories c ON i.cat_id = c.cat_id
    LEFT JOIN departments d ON i.dept_id = d.dept_id
    LEFT JOIN employees e ON i.emp_id = e.emp_id
    LEFT JOIN departments ed ON e.dept_id = ed.dept_id
    WHERE i.item_id = ?
";

$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $id);
$stmt->execute();
$result = $stmt->get_result();

$item = $result->fetch_assoc();

if ($item) {
    echo json_encode(['success' => true, 'item' => $item]);
} else {
    echo json_encode(['success' => false, 'message' => 'Item not found']);
}