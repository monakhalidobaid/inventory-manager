<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../../config/db.php';
require_once '../../config/secure_page.php';
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// 🔒 Check if user is admin
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Access denied. Admins only.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$id = isset($input['id']) ? intval($input['id']) : 0;

if ($id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid department ID.']);
    exit;
}

try {
    // ✅ فحص 1: التحقق من وجود موظفين
    $checkEmp = $conn->prepare("SELECT COUNT(*) as count FROM employees WHERE dept_id = ?");
    $checkEmp->bind_param('i', $id);
    $checkEmp->execute();
    $empResult = $checkEmp->get_result();
    $empCount = $empResult->fetch_assoc()['count'];
    $checkEmp->close();
    
    if ($empCount > 0) {
        echo json_encode([
            'success' => false,
            'message' => "Cannot delete this department. It has {$empCount} employee(s) assigned to it. Please remove or reassign the employees first."
        ]);
        exit;
    }
    
    // ✅ فحص 2: التحقق من وجود items
    $checkItem = $conn->prepare("SELECT COUNT(*) as count FROM item WHERE dept_id = ?");
    $checkItem->bind_param('i', $id);
    $checkItem->execute();
    $itemResult = $checkItem->get_result();
    $itemCount = $itemResult->fetch_assoc()['count'];
    $checkItem->close();
    
    if ($itemCount > 0) {
        echo json_encode([
            'success' => false,
            'message' => "Cannot delete this department. It has {$itemCount} item(s) assigned to it. Please remove or reassign the items first."
        ]);
        exit;
    }
    
    // ✅ إذا لم يكن هناك موظفين ولا items، قم بالحذف
    $stmt = $conn->prepare("DELETE FROM departments WHERE dept_id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        echo json_encode(['success' => true, 'message' => 'Department deleted successfully.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Department not found.']);
    }

    $stmt->close();
    $conn->close();

} catch (mysqli_sql_exception $e) {
    // ✅ معالجة أي أخطاء غير متوقعة
    if ($e->getCode() == 1451) {
        $errorMessage = $e->getMessage();
        
        if (strpos($errorMessage, '`employees`') !== false) {
            echo json_encode([
                'success' => false,
                'message' => 'Cannot delete this department. It has employees assigned to it. Please remove or reassign the employees first.'
            ]);
        } 
        elseif (strpos($errorMessage, '`item`') !== false) {
            echo json_encode([
                'success' => false,
                'message' => 'Cannot delete this department. It has items assigned to it. Please remove or reassign the items first.'
            ]);
        }
        else {
            echo json_encode([
                'success' => false,
                'message' => 'Cannot delete this department. It has related records in the system that must be removed first.'
            ]);
        }
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'An error occurred while deleting the department: ' . $e->getMessage()
        ]);
    }
}
?>