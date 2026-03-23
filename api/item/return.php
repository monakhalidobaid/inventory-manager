<?php
header('Content-Type: application/json');
include_once '../../config/db.php';
include_once '../../config/secure_page.php';

// التأكد من أن المستخدم admin
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized access'
    ]);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$item_id = isset($input['item_id']) ? (int)$input['item_id'] : null;

if (!$item_id) {
    echo json_encode([
        'success' => false,
        'message' => 'Item ID is required'
    ]);
    exit;
}

// التحقق من وجود العنصر
$checkStmt = $conn->prepare("SELECT item_id, dept_id, emp_id, item_status FROM item WHERE item_id = ?");
$checkStmt->bind_param("i", $item_id);
$checkStmt->execute();
$result = $checkStmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Item not found'
    ]);
    exit;
}

$currentItem = $result->fetch_assoc();

// ✅ التحقق من أن العنصر معين (له قسم أو موظف)
if (is_null($currentItem['dept_id']) && is_null($currentItem['emp_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Cannot return an unassigned item. The item must be assigned to a department or employee first.'
    ]);
    exit;
}

// حفظ الحالة القديمة للـ transaction
$old_status = $currentItem['item_status'];
$old_dept_id = $currentItem['dept_id'];
$old_emp_id = $currentItem['emp_id'];
$user_id = $_SESSION['user_id']; // ✅ استخدام ID بدلاً من user_name

// بداية Transaction
$conn->begin_transaction();

try {
    // 1. تحديث العنصر
    $stmt = $conn->prepare("
        UPDATE item 
        SET dept_id = NULL, emp_id = NULL, item_status = 'Standby'
        WHERE item_id = ?
    ");
    $stmt->bind_param("i", $item_id);
    
    if (!$stmt->execute()) {
        throw new Exception('Failed to return item');
    }
    
    // 2. تسجيل العملية في جدول item_transactions
    // ✅ to_emp_id و to_dept_id يجب أن يكونوا NULL لأننا نرجع العنصر
    $transStmt = $conn->prepare("
        INSERT INTO item_transactions 
        (item_id, trans_type, from_emp_id, from_dept_id, to_emp_id, to_dept_id, old_status, new_status, created_by)
        VALUES (?, 'return', ?, ?, NULL, NULL, ?, 'Standby', ?)
    ");
    $transStmt->bind_param(
        "iiisi", 
        $item_id, 
        $old_emp_id,      // from_emp_id (القيمة القديمة)
        $old_dept_id,     // from_dept_id (القيمة القديمة)
        $old_status,      // old_status
        $user_id          // ✅ created_by (user ID)
    );
    
    if (!$transStmt->execute()) {
        throw new Exception('Failed to log transaction');
    }
    
    // Commit Transaction
    $conn->commit();
    
    // بناء رسالة النجاح
    $message = "Item returned successfully and status changed to Standby";
    
    echo json_encode([
        'success' => true,
        'message' => $message
    ]);
    
} catch (Exception $e) {
    // Rollback في حالة الخطأ
    $conn->rollback();
    
    echo json_encode([
        'success' => false,
        'message' => 'Failed to return item: ' . $e->getMessage()
    ]);
}

$conn->close();
?>