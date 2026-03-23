<?php
header('Content-Type: application/json; charset=utf-8');

require_once '../../config/db.php';
require_once '../../config/secure_page.php';

// ==============================
// 🧩 1. Read and validate input
// ==============================
$input = json_decode(file_get_contents('php://input'), true);
$id = isset($input['id']) ? intval($input['id']) : 0;

if ($id <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid employee ID.'
    ]);
    exit;
}

// ==============================
// ⚙️ 2. Execute delete operation
// ==============================
try {
    $stmt = $conn->prepare("DELETE FROM employees WHERE emp_id = ?");
    $stmt->bind_param('i', $id);

    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Employee deleted successfully.'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to delete employee.'
        ]);
    }

    $stmt->close();
    $conn->close();

// ==============================
// ⚠️ 3. Handle errors
// ==============================
} catch (mysqli_sql_exception $e) {

    // 1451 = Foreign Key Constraint (employee is linked to other records)
    if ($e->getCode() == 1451) {
        echo json_encode([
            'success' => false,
            'message' => 'Cannot delete this employee because they are linked to other items.'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'An error occurred while deleting the employee: ' . $e->getMessage()
        ]);
    }
}
?>
