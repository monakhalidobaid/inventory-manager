<?php
// /inventory_manager/api/transaction/get_transaction.php

header('Content-Type: application/json');
include_once '../../config/db.php';
include_once '../../config/secure_page.php';

try {
    // استقبال المعاملات
    $q = isset($_GET['q']) ? trim($_GET['q']) : '';
    $transaction = isset($_GET['transaction']) ? trim($_GET['transaction']) : '';
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 5;
    $offset = ($page - 1) * $limit;
    
    // ✅ بناء الاستعلام الأساسي مع JOIN لجلب اسم المستخدم و item_code
    $sql = "SELECT 
                t.trans_id,
                t.item_id,
                i.item_code,
                t.trans_type,
                t.trans_date,
                t.created_by,
                u.user_name as created_by_name
            FROM item_transactions t
            LEFT JOIN users u ON t.created_by = u.id
            LEFT JOIN item i ON t.item_id = i.item_id
            WHERE 1=1";
    
    // ✅ استعلام العد مع JOIN
    $countSql = "SELECT COUNT(*) as total 
                 FROM item_transactions t 
                 LEFT JOIN users u ON t.created_by = u.id 
                 LEFT JOIN item i ON t.item_id = i.item_id
                 WHERE 1=1";
    
    $params = [];
    $types = "";
    
    // ✅ فلتر البحث (ID أو item_id أو item_code أو اسم المستخدم)
    if (!empty($q)) {
        $sql .= " AND (t.trans_id LIKE ? OR t.item_id LIKE ? OR i.item_code LIKE ? OR u.user_name LIKE ?)";
        $countSql .= " AND (t.trans_id LIKE ? OR t.item_id LIKE ? OR i.item_code LIKE ? OR u.user_name LIKE ?)";
        $searchParam = "%{$q}%";
        $params[] = $searchParam;
        $params[] = $searchParam;
        $params[] = $searchParam;
        $params[] = $searchParam;
        $types .= "ssss";
    }
    
    // فلتر نوع المعاملة
    if (!empty($transaction)) {
        $sql .= " AND t.trans_type = ?";
        $countSql .= " AND t.trans_type = ?";
        $params[] = $transaction;
        $types .= "s";
    }
    
    // ترتيب وحد النتائج
    $sql .= " ORDER BY t.trans_date DESC, t.trans_id DESC LIMIT ? OFFSET ?";
    $params[] = $limit;
    $params[] = $offset;
    $types .= "ii";
    
    // تنفيذ استعلام العد
    $countParams = array_slice($params, 0, count($params) - 2);
    $countTypes = substr($types, 0, -2);
    
    $countStmt = $conn->prepare($countSql);
    if (!empty($countParams)) {
        $countStmt->bind_param($countTypes, ...$countParams);
    }
    $countStmt->execute();
    $totalResult = $countStmt->get_result();
    $total = $totalResult->fetch_assoc()['total'];
    $countStmt->close();
    
    // تنفيذ الاستعلام الرئيسي
    $stmt = $conn->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    
    $items = [];
    while ($row = $result->fetch_assoc()) {
        $items[] = $row;
    }
    
    $stmt->close();
    
    echo json_encode([
        'success' => true,
        'item' => $items,
        'total' => (int)$total,
        'page' => $page,
        'limit' => $limit
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>