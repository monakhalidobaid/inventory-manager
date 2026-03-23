<?php
// C:\xampp\htdocs\inventory_manager\api\item\get_dashboard_stats.php

include_once '../../config/db.php';
include_once '../../config/secure_page.php';

header('Content-Type: application/json');

// إجمالي الأصناف
$sqlTotal = "SELECT COUNT(*) as total FROM item";
$resultTotal = $conn->query($sqlTotal);
$totalItems = 0;
if ($resultTotal && $resultTotal->num_rows > 0) {
    $row = $resultTotal->fetch_assoc();
    $totalItems = (int)$row['total'];
}

// الأصناف النشطة
$sqlActive = "SELECT COUNT(*) as active FROM item WHERE item_status = 'Active'";
$resultActive = $conn->query($sqlActive);
$activeItems = 0;
if ($resultActive && $resultActive->num_rows > 0) {
    $row = $resultActive->fetch_assoc();
    $activeItems = (int)$row['active'];
}

echo json_encode([
    'success' => true,
    'data' => [
        'total_items' => $totalItems,
        'active_items' => $activeItems
    ]
], JSON_UNESCAPED_UNICODE);

$conn->close();
?>