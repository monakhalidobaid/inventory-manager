<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../../config/db.php';
require_once '../../config/secure_page.php';

// 🔒 تحقق من نوع المستخدم
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Access denied. Admins only.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$id = isset($input['id']) ? intval($input['id']) : 0;

if ($id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid ID']);
    exit;
}

try {
    $stmt = $conn->prepare("DELETE FROM categories WHERE cat_id = ?");
    $stmt->bind_param('i', $id);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Category deleted successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Delete failed']);
    }

    $stmt->close();
    $conn->close();

} catch (mysqli_sql_exception $e) {
    // ⚠️ 23000 = foreign key constraint violation
    if ($e->getCode() == 1451) {
        echo json_encode([
            'success' => false,
            'message' => 'Cannot delete a category that has assigned items. <br>Please remove or reassign them first.'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'An error occurred while deleting the department: ' . $e->getMessage()
        ]);
    }
}