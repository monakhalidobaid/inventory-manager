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
    $cat_name = isset($input['catNameValue']) ? trim($input['catNameValue']) : '';

    if ($cat_name  === '') {
        echo json_encode(['success' => false, 'message' => 'Category name is required']);
        exit;
    }

    // 🔍 التحقق أولاً إذا كان الاسم موجود (بغض النظر عن حالة الأحرف)
    $checkStmt = $conn->prepare("SELECT cat_id FROM categories WHERE LOWER(cat_name) = LOWER(?)");
    $checkStmt->bind_param('s', $cat_name);
    $checkStmt->execute();
    $checkStmt->store_result();

    if ($checkStmt->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Category name already exists']);
        $checkStmt->close();
        $conn->close();
        exit;
    }
    $checkStmt->close();

    // ✅ لو مو موجود، نسوي الإدخال
    $stmt = $conn->prepare('INSERT INTO categories (cat_name) VALUES (?)');
    $stmt->bind_param('s', $cat_name);

    if ($stmt->execute()) {
        $result = $conn->query('SELECT * FROM categories');
        $category = $result->fetch_all(MYSQLI_ASSOC);

        echo json_encode([
            'success' => true,
            'message' => 'Category added successfully',
            'category' => $category
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Category insert failed']);
    }

    $stmt->close();
    $conn->close();
}
?>
