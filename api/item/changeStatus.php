<?php

header('Content-Type: application/json; charset=utf-8');
require_once '../../config/db.php';
require_once '../../config/secure_page.php';

// تحقق من نوع المستخدم
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Access denied. Admins only.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

$item_id = isset($input['item_id']) ? intval($input['item_id']) : 0;
$item_status = isset($input['item_status']) ? trim($input['item_status']) : '';
$user_id = $_SESSION['user_id']; // ✅ استخدام ID بدلاً من user_name

//  مصفوفة الأخطاء
$errors = [];

// التحقق من صحة البيانات
if ($item_id <= 0) {
    $errors['item_id'] = 'Invalid item ID';
}

// التحقق من نوع العنصر
if (!$item_status) {
    $errors['statusOption'] = 'Status can not be empty'; // ✅ غيّر من item_type إلى statusOption
}
// إذا كان هناك أخطاء، نرجعها
if (!empty($errors)) {
    echo json_encode([
        'success' => false,
        'errors' => $errors
    ]);
    exit;
}

// التحقق من وجود العنصر
$checkStmt = $conn->prepare("SELECT item_id, item_status FROM item WHERE item_id = ?");
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

// حفظ الحالة القديمة للـ transaction
$old_status = $currentItem['item_status'];

try {
    //  بداية Transaction
    $conn->begin_transaction();

    $stmt = $conn->prepare("UPDATE item SET item_status = ? WHERE item_id = ?");
    $stmt->bind_param("si", $item_status, $item_id);

    if (!$stmt->execute()) {
        throw new Exception('Failed to update item');
    }

    $tranStmt = $conn->prepare("INSERT INTO item_transactions
    (item_id, trans_type, old_status, new_status, created_by)
    VALUES(?, 'changeStatus', ?, ?, ?)");
    $tranStmt->bind_param("issi",
        $item_id,
        $old_status,
        $item_status,
        $user_id          // ✅ created_by (user ID)
    );

    if (!$tranStmt->execute()) {
        throw new Exception('Failed to log transaction');
    }

    $conn->commit();

    // بناء رسالة النجاح
    $message = "Item status successfully changed";
    
    echo json_encode([
        'success' => true,
        'message' => $message
    ]);
    
} catch (Exception $e) {
    // Rollback في حالة الخطأ
    $conn->rollback();
    
    echo json_encode([
        'success' => false,
        'message' => 'Failed to update item: ' . $e->getMessage()
    ]);
}

$conn->close();
?>