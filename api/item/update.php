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
$item_type = isset($input['item_type']) ? trim($input['item_type']) : '';

// مصفوفة الأخطاء
$errors = [];

// التحقق من معرف العنصر
if ($item_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid item ID']);
    exit;
}

// التحقق من نوع العنصر
if (!in_array($item_type, ['software', 'hardware'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid item type']);
    exit;
}

// تفعيل الاستثناءات للـ mysqli
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    // بداية Transaction
    $conn->begin_transaction();

    // جلب البيانات القديمة قبل التحديث لتسجيلها في notes
    $oldDataStmt = $conn->prepare("SELECT cat_id, purchase_date, warranty_months, warranty_end,
                                    warranty_status, description FROM item WHERE item_id = ?");
    $oldDataStmt->bind_param('i', $item_id);
    $oldDataStmt->execute();
    $oldDataResult = $oldDataStmt->get_result();
    $oldData = $oldDataResult->fetch_assoc();
    $oldDataStmt->close();

    if (!$oldData) {
        throw new Exception('Item not found');
    }

    $changes = []; // لتسجيل التغييرات في notes

    if ($item_type === 'software') {
        // Validation لـ Software
        $description = isset($input['description']) ? trim($input['description']) : '';
       
        // التحقق من الوصف
        if (empty($description)) {
            $errors['description'] = 'Description is required for software items';
        }

        // إذا كانت هناك أخطاء، إرجاعها
        if (!empty($errors)) {
            echo json_encode(['success' => false, 'errors' => $errors]);
            exit;
        }

        // تسجيل التغييرات
        if ($oldData['description'] !== $description) {
            $changes[] = "Description: '{$oldData['description']}' → '{$description}'";
        }
       
        // تحديث Software
        $stmt = $conn->prepare("UPDATE item SET description = ? WHERE item_id = ?");
        $stmt->bind_param('si', $description, $item_id);
        $stmt->execute();

    } else if ($item_type === 'hardware') {
        // Validation لـ Hardware
        $cat_id = isset($input['cat_id']) ? intval($input['cat_id']) : 0;
        $purchase_date = isset($input['purchase_date']) && $input['purchase_date'] !== ''
            ? trim($input['purchase_date']) : null;
        $warranty_months = isset($input['warranty_months']) && $input['warranty_months'] !== ''
            ? trim($input['warranty_months']) : null;
        $warranty_end = isset($input['warranty_end']) && $input['warranty_end'] !== ''
            ? trim($input['warranty_end']) : null;
        $description = isset($input['description']) ? trim($input['description']) : '';

        // التحقق من cat_id
        if ($cat_id <= 0) {
            $errors['cat_id'] = 'Please select a valid category';
        } else {
            // التحقق من وجود الفئة في قاعدة البيانات
            $checkCat = $conn->prepare("SELECT cat_id FROM categories WHERE cat_id = ?");
            $checkCat->bind_param('i', $cat_id);
            $checkCat->execute();
            $result = $checkCat->get_result();
            if ($result->num_rows === 0) {
                $errors['cat_id'] = 'Selected category does not exist';
            }
            $checkCat->close();
        }

        // التحقق من warranty_months
        if ($warranty_months !== null && $warranty_months !== '') {
            // التحقق من وجود purchase_date أولاً
            if ($purchase_date === null || $purchase_date === '') {
                $errors['warranty_months'] = 'Cannot add warranty months without purchase date';
            } else {
                // التحقق من أنها أرقام فقط
                if (!ctype_digit($warranty_months)) {
                    $errors['warranty_months'] = 'Warranty months must contain only numbers';
                } else {
                    $warranty_months_int = intval($warranty_months);
                    // التحقق من أنها أكبر من صفر
                    if ($warranty_months_int <= 0) {
                        $errors['warranty_months'] = 'Warranty months must be greater than zero';
                    }
                }
            }
        }

        // التحقق من purchase_date
        if ($purchase_date !== null && $purchase_date !== '') {
            // التحقق من صحة تنسيق التاريخ
            $date_parts = explode('-', $purchase_date);
            if (count($date_parts) !== 3 || !checkdate($date_parts[1], $date_parts[2], $date_parts[0])) {
                $errors['purchase_date'] = 'Invalid date format. Please use YYYY-MM-DD';
            }
        }

        // إذا كانت هناك أخطاء، إرجاعها
        if (!empty($errors)) {
            echo json_encode(['success' => false, 'errors' => $errors]);
            exit;
        }

        // تحويل warranty_months إلى integer بعد التحقق
        $warranty_months_final = ($warranty_months !== null && $warranty_months !== '')
            ? intval($warranty_months) : null;

        // حساب warranty_status بناءً على warranty_end
        $warranty_status = 'No warranty info';
        if ($warranty_end !== null && $warranty_end !== '') {
            $today = date('Y-m-d');
            if ($warranty_end < $today) {
                $warranty_status = 'Warranty expired';
            } else {
                $warranty_status = 'Under warranty';
            }
        }

        // تسجيل التغييرات
        if ($oldData['cat_id'] != $cat_id) {
            $changes[] = "Category ID: {$oldData['cat_id']} → {$cat_id}";
        }
        if ($oldData['purchase_date'] !== $purchase_date) {
            $old_pd = $oldData['purchase_date'] ?? 'NULL';
            $new_pd = $purchase_date ?? 'NULL';
            $changes[] = "Purchase Date: {$old_pd} → {$new_pd}";
        }
        if ($oldData['warranty_months'] != $warranty_months_final) {
            $old_wm = $oldData['warranty_months'] ?? 'NULL';
            $new_wm = $warranty_months_final ?? 'NULL';
            $changes[] = "Warranty Months: {$old_wm} → {$new_wm}";
        }
        if ($oldData['warranty_end'] !== $warranty_end) {
            $old_we = $oldData['warranty_end'] ?? 'NULL';
            $new_we = $warranty_end ?? 'NULL';
            $changes[] = "Warranty End: {$old_we} → {$new_we}";
        }
        if ($oldData['warranty_status'] !== $warranty_status) {
            $changes[] = "Warranty Status: '{$oldData['warranty_status']}' → '{$warranty_status}'";
        }
        if ($oldData['description'] !== $description) {
            $changes[] = "Description: '{$oldData['description']}' → '{$description}'";
        }

        // تحديث Hardware
        $stmt = $conn->prepare("UPDATE item
            SET cat_id = ?,
                purchase_date = ?,
                warranty_months = ?,
                warranty_end = ?,
                warranty_status = ?,
                description = ?
            WHERE item_id = ?");
       
        $stmt->bind_param('isisssi',
            $cat_id,
            $purchase_date,
            $warranty_months_final,
            $warranty_end,
            $warranty_status,
            $description,
            $item_id
        );
        $stmt->execute();
    }

    // إذا حدث تحديث فعلي
    if ($stmt->affected_rows > 0) {
        // إنشاء notes من التغييرات
        $notes = !empty($changes) ? implode(', ', $changes) : 'Item data updated';

        // ✅ تخزين user_id بدلاً من user_name
        $user_id = $_SESSION['user_id']; // استخدام ID بدلاً من الاسم

        // تسجيل Transaction
        $transStmt = $conn->prepare("INSERT INTO item_transactions
            (item_id, trans_type, notes, created_by)
            VALUES (?, 'edit', ?, ?)");
       
        $transStmt->bind_param('isi', $item_id, $notes, $user_id);
        $transStmt->execute();
        $transStmt->close();

        // Commit Transaction
        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Item updated successfully']);
    } else {
        // لا يوجد تغيير
        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'No changes made']);
    }
   
    $stmt->close();
    $conn->close();

} catch (mysqli_sql_exception $e) {
    // Rollback في حالة الخطأ
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'Update failed: ' . $e->getMessage()]);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>