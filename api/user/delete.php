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

// ✅ تفعيل استثناءات mysqli
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    $stmt->bind_param('i', $id);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'User deleted successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Delete failed']);
    }

    $stmt->close();
    $conn->close();

} catch (mysqli_sql_exception $e) {
    // ✅ معالجة خطأ Foreign Key constraint
    if ($e->getCode() == 1451) {
        echo json_encode([
            'success' => false, 
            'message' => 'Cannot delete this user. This user has created transactions in the system and cannot be removed.'
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>