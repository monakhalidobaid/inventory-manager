<?php
include_once '../../config/db.php';
include_once '../../config/secure_page.php';

header('Content-Type: application/json; charset=utf-8');

// نستخدم JOIN بين item و department
$sql = "
    SELECT 
        d.dept_name AS department_name,
        COUNT(i.item_id) AS total_items
    FROM item i
    JOIN departments d ON i.dept_id = d.dept_id
    GROUP BY d.dept_name
    ORDER BY total_items DESC
";

$result = $conn->query($sql);
$data = [];

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $data[] = [
            'department_name' => $row['department_name'],
            'total_items' => (int)$row['total_items']
        ];
    }
}

echo json_encode([
    'success' => true,
    'data' => $data
], JSON_UNESCAPED_UNICODE);

$conn->close();
?>
