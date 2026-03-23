<?php
require_once '../../config/db.php'; // استدعاء الاتصال
require_once '../../config/secure_page.php';

header('Content-Type: application/json');

// 🔒 تحقق من نوع المستخدم
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Access denied. Admins only.']);
    exit;
}

// استقبل JSON
$input = json_decode(file_get_contents('php://input'), true);

if ($input) {
    $dept_name = isset($input['deptNameValue']) ? trim($input['deptNameValue']) : '';

    if ($dept_name === '') {
        echo json_encode(['success' => false, 'message' => 'Department name is required']);
        exit;
    }

    // 🔍 التحقق أولاً إذا كان الاسم موجود (بغض النظر عن حالة الأحرف)
    $checkStmt = $conn->prepare("SELECT dept_id FROM departments WHERE LOWER(dept_name) = LOWER(?)");
    $checkStmt->bind_param('s', $dept_name);
    $checkStmt->execute();
    $checkStmt->store_result();

    if ($checkStmt->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Department name already exists']);
        $checkStmt->close();
        $conn->close();
        exit;
    }
    $checkStmt->close();

    // ✅ لو مو موجود، نسوي الإدخال
    $stmt = $conn->prepare('INSERT INTO departments (dept_name) VALUES (?)');
    $stmt->bind_param('s', $dept_name);

    if ($stmt->execute()) {
        $result = $conn->query('SELECT * FROM departments');
        $departments = $result->fetch_all(MYSQLI_ASSOC);

        echo json_encode([
            'success' => true,
            'message' => 'Department added successfully',
            'departments' => $departments
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Department insert failed']);
    }

    $stmt->close();
    $conn->close();
}
?>
