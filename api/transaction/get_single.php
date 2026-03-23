<?php
// /inventory_manager/api/transaction/get_single.php

header('Content-Type: application/json');
include_once '../../config/db.php';
include_once '../../config/secure_page.php';

try {
    // التحقق من وجود المعرف
    if (!isset($_GET['id']) || empty($_GET['id'])) {
        throw new Exception('Transaction ID is required');
    }
    
    $trans_id = (int)$_GET['id'];
    
    // استعلام لجلب كل التفاصيل مع أسماء الموظفين والأقسام واسم المستخدم
    $sql = "SELECT 
                t.trans_id,
                t.item_id,
                i.item_code,
                t.trans_type,
                t.trans_date,
                t.from_emp_id,
                t.from_dept_id,
                t.to_emp_id,
                t.to_dept_id,
                t.old_status,
                t.new_status,
                t.notes,
                t.created_by,
                -- ✅ إضافة اسم المستخدم الذي أنشأ المعاملة
                u.user_name as created_by_name,
                -- أسماء الموظفين
                from_emp.emp_name as from_emp_name,
                to_emp.emp_name as to_emp_name,
                -- أسماء الأقسام
                from_dept.dept_name as from_dept_name,
                to_dept.dept_name as to_dept_name
            FROM item_transactions t
            LEFT JOIN item i ON t.item_id = i.item_id
            LEFT JOIN users u ON t.created_by = u.id
            LEFT JOIN employees from_emp ON t.from_emp_id = from_emp.emp_id
            LEFT JOIN employees to_emp ON t.to_emp_id = to_emp.emp_id
            LEFT JOIN departments from_dept ON t.from_dept_id = from_dept.dept_id
            LEFT JOIN departments to_dept ON t.to_dept_id = to_dept.dept_id
            WHERE t.trans_id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $trans_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception('Transaction not found');
    }
    
    $item = $result->fetch_assoc();
    $stmt->close();
    
    echo json_encode([
        'success' => true,
        'item' => $item
    ]);
    
} catch (Exception $e) {
    http_response_code(404);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>