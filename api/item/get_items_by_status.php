<?php
include_once '../../config/db.php';
include_once '../../config/secure_page.php';

header('Content-Type: application/json; charset=utf-8');

$sql = "
    SELECT 
        item_status, 
        COUNT(*) AS total 
    FROM item 
    GROUP BY item_status
";

$result = $conn->query($sql);
$data = [];

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $data[] = [
            'status' => $row['item_status'],
            'total' => (int)$row['total']
        ];
    }
}

echo json_encode([
    'success' => true,
    'data' => $data
], JSON_UNESCAPED_UNICODE);

$conn->close();
?>
