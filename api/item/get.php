<?php
header('Content-Type: application/json');
include_once '../../config/db.php';
include_once '../../config/secure_page.php';

$q = isset($_GET['q']) ? trim($_GET['q']) : '';
$dept = isset($_GET['dept']) ? trim($_GET['dept']) : '';
$cat = isset($_GET['cat']) ? trim($_GET['cat']) : '';
$status = isset($_GET['status']) ? trim($_GET['status']) : '';
$type = isset($_GET['type']) ? trim($_GET['type']) : '';
$page  = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 5;
$offset = ($page - 1) * $limit;

$where = [];
$params = [];
$types = '';

// فلتر البحث - محسّن للبحث في جميع الحقول المطلوبة
if ($q !== '') {
    $where[] = "(i.item_code LIKE ? OR i.type LIKE ? OR d.dept_name LIKE ? OR e.emp_name LIKE ? OR c.cat_name LIKE ?)";
    $search = "%$q%";
    $params[] = $search;
    $params[] = $search;
    $params[] = $search;
    $params[] = $search;
    $params[] = $search;
    $types .= 'sssss';
}

// فلتر القسم
if ($dept !== '') {
    $where[] = "i.dept_id = ?";
    $params[] = $dept;
    $types .= 'i';
}

// فلتر التصنيف
if ($cat !== '') {
    $where[] = "i.cat_id = ?";
    $params[] = $cat;
    $types .= 'i';
}

// فلتر الحالة
if ($status !== '') {
    $where[] = "i.item_status = ?";
    $params[] = $status;
    $types .= 's';
}

// فلتر النوع
if ($type !== '') {
    $where[] = "i.type = ?";
    $params[] = $type;
    $types .= 's';
}

$whereSQL = '';
if (!empty($where)) {
    $whereSQL = 'WHERE ' . implode(' AND ', $where);
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
        ed.dept_name AS emp_dept_name,
        i.cat_id
    FROM item i
    LEFT JOIN categories c ON i.cat_id = c.cat_id
    LEFT JOIN departments d ON i.dept_id = d.dept_id
    LEFT JOIN employees e ON i.emp_id = e.emp_id
    LEFT JOIN departments ed ON e.dept_id = ed.dept_id
    $whereSQL
    ORDER BY i.item_id DESC
    LIMIT ? OFFSET ?
";

$params[] = $limit;
$params[] = $offset;
$types .= 'ii';

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

$items = [];
while ($row = $result->fetch_assoc()) {
    $items[] = $row;
}

// حساب المجموع الكلي للصفحات
$countSql = "SELECT COUNT(*) AS total FROM item i
             LEFT JOIN categories c ON i.cat_id = c.cat_id
             LEFT JOIN departments d ON i.dept_id = d.dept_id
             LEFT JOIN employees e ON i.emp_id = e.emp_id
             $whereSQL";

$countStmt = $conn->prepare($countSql);
if (!empty($where)) {
    // عدد المعاملات بدون الـ limit و offset
    $countTypes = substr($types, 0, -2);
    $countParams = array_slice($params, 0, -2);
    $countStmt->bind_param($countTypes, ...$countParams);
}
$countStmt->execute();
$total = $countStmt->get_result()->fetch_assoc()['total'];

echo json_encode([
    'success' => true,
    'item' => $items,
    'total' => $total,
    'page' => $page,
    'limit' => $limit
]);