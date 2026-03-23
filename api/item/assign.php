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

// قراءة البيانات من الطلب
$input = json_decode(file_get_contents('php://input'), true);

$item_id = isset($input['item_id']) ? (int)$input['item_id'] : null;
$item_type = isset($input['item_type']) ? trim($input['item_type']) : null;
$dept_id = isset($input['dept_id']) ? (int)$input['dept_id'] : null;
$emp_id = isset($input['emp_id']) && $input['emp_id'] !== '' ? (int)$input['emp_id'] : null;
$user_id = $_SESSION['user_id']; // ✅ استخدام ID بدلاً من user_name

$errors = [];

// التحقق من صحة البيانات
if (!$item_id) {
    $errors['item_id'] = 'Item ID is required';
}

if (!$item_type) {
    $errors['item_type'] = 'Item type is required';
}

if (!$dept_id) {
    $errors['dept_id'] = 'Department is required';
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

// فكرة 4: التحقق من أن العنصر ليس معين بالكامل بالفعل
if ($currentItem['dept_id'] && $currentItem['emp_id']) {
    echo json_encode([
        'success' => false,
        'message' => 'Item is already fully assigned. Use Transfer to change assignment.'
    ]);
    exit;
}

// حفظ الحالة القديمة للـ transaction
$old_status = $currentItem['item_status'];
$old_dept_id = $currentItem['dept_id'];
$old_emp_id = $currentItem['emp_id'];

// بداية Transaction
$conn->begin_transaction();

try {
    // 1. تحديث جدول item
    if ($emp_id) {
        // إذا تم اختيار موظف
        $stmt = $conn->prepare("
            UPDATE item 
            SET dept_id = ?, emp_id = ?, item_status = 'Active'
            WHERE item_id = ?
        ");
        $stmt->bind_param("iii", $dept_id, $emp_id, $item_id);
    } else {
        // إذا اختار القسم فقط بدون موظف
        $stmt = $conn->prepare("
            UPDATE item 
            SET dept_id = ?, emp_id = NULL, item_status = 'Active'
            WHERE item_id = ?
        ");
        $stmt->bind_param("ii", $dept_id, $item_id);
    }
    
    if (!$stmt->execute()) {
        throw new Exception('Failed to update item');
    }
    
    // 2. تسجيل العملية في جدول item_transactions
    $transStmt = $conn->prepare("
        INSERT INTO item_transactions 
        (item_id, trans_type, from_emp_id, from_dept_id, to_emp_id, to_dept_id, old_status, new_status, created_by)
        VALUES (?, 'assign', ?, ?, ?, ?, ?, 'Active', ?)
    ");
    $transStmt->bind_param(
        "iiiiisi", 
        $item_id, 
        $old_emp_id,      // from_emp_id (القيمة القديمة)
        $old_dept_id,     // from_dept_id (القيمة القديمة)
        $emp_id,          // to_emp_id (القيمة الجديدة)
        $dept_id,         // to_dept_id (القيمة الجديدة)
        $old_status,      // old_status
        $user_id          // ✅ created_by (user ID)
    );
    
    if (!$transStmt->execute()) {
        throw new Exception('Failed to log transaction');
    }
    
    // 3. جلب معلومات القسم والموظف للرسالة
    $deptName = '';
    $empName = '';
    
    $deptStmt = $conn->prepare("SELECT dept_name FROM departments WHERE dept_id = ?");
    $deptStmt->bind_param("i", $dept_id);
    $deptStmt->execute();
    $deptResult = $deptStmt->get_result();
    if ($deptRow = $deptResult->fetch_assoc()) {
        $deptName = $deptRow['dept_name'];
    }
    
    if ($emp_id) {
        $empStmt = $conn->prepare("SELECT emp_name FROM employees WHERE emp_id = ?");
        $empStmt->bind_param("i", $emp_id);
        $empStmt->execute();
        $empResult = $empStmt->get_result();
        if ($empRow = $empResult->fetch_assoc()) {
            $empName = $empRow['emp_name'];
        }
    }
    
    // Commit Transaction
    $conn->commit();
    
    // بناء رسالة النجاح
    $message = $emp_id 
        ? "Item successfully assigned to $empName ($deptName)" 
        : "Item successfully assigned to $deptName department";
    
    echo json_encode([
        'success' => true,
        'message' => $message
    ]);
    
} catch (Exception $e) {
    // Rollback في حالة الخطأ
    $conn->rollback();
    
    echo json_encode([
        'success' => false,
        'message' => 'Failed to assign item: ' . $e->getMessage()
    ]);
}

$conn->close();
?>