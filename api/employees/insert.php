<?php
require_once '../../config/db.php';
require_once '../../config/secure_page.php';
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    echo json_encode(["success" => false, "message" => "No input received"]);
    exit;
}

$employee_name = trim($data['employee_name'] ?? '');
$employee_no = trim($data['employee_no'] ?? '');
$emp_status = trim($data['emp_status'] ?? '');
$hire_date = trim($data['hire_date'] ?? '');
$contract_d = intval($data['contract_d'] ?? '');
$department = intval($data['department'] ?? 0); // dept_id رقم

// مصفوفة للأخطاء
$errors = [];

// 1- التحقق من الاسم
if (empty($employee_name)) {
    $errors['employee_name'] = "Username is required";
} else {
    // فقط حروف عربية أو انجليزية ومسافة
    if (!preg_match('/^[\p{Arabic}a-zA-Z\s]+$/u', $employee_name)) {
        $errors['employee_name'] = "Name must contain only Arabic or English letters";
    }
}

// 2- رقم الموظف (على الأقل 4 أحرف)
if (strlen($employee_no) < 4) {
    $errors['employee_no'] = "Employee number must be at least 4 characters";
}

// 3- حالة الموظف
if (empty($emp_status)) {
    $errors['emp_status'] = "Please select employee status";
}

// 4- تاريخ التعيين
if (empty($hire_date)) {
    $errors['hire_date'] = "Please select employee hire date";
}

// 5- مدة العقد
if (empty($contract_d) || intval($contract_d) < 1) {
    $errors['contract_d'] = "Contract duration must be at least 1 year";
}

// 6- القسم
if (empty($department)) {
    $errors['department'] = "Please select a department";
}

// إذا فيه أخطاء نرجعها
if (!empty($errors)) {
    echo json_encode(["success" => false, "errors" => $errors]);
    exit;
}

// --- حساب تاريخ نهاية العقد ---
$contract_end_date = null;
try {
    $dt = new DateTime($hire_date);
    $dt->modify('+' . $contract_d . ' years');
    $contract_end_date = $dt->format('Y-m-d');
} catch (Exception $e) {
    // تنسيق تاريخ غير صالح
    echo json_encode(["success" => false, "errors" => ["hire_date" => "Invalid hire date"]]);
    exit;
}

// استخدم أسلوب mysqli OO مع تفعيل استثناءات MySQLi لالتقاط الأخطاء في catch
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $sql = "INSERT INTO employees 
            (emp_name, emp_no, status, hire_date, contract_end_date, dept_id)
            VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param(
        "sssssi",
        $employee_name,
        $employee_no,
        $emp_status,
        $hire_date,
        $contract_end_date,
        $department
    );
    $stmt->execute();

    echo json_encode(["success" => true, "message" => "Employee added successfully"]);

} catch (mysqli_sql_exception $e) {
    // تحقق إذا كان خطأ تكرار emp_no (مثلاً مفتاح فريد على emp_no)
    if ($e->getCode() == 1062) {
        // رسائل واضحة للمستخدم
        echo json_encode(["success" => false, "message" => "Employee number already exists. Please choose another."]);
    } else {
        // رسالة عامة للـ server مع تفاصيل الخطأ (يمكن إزالة $e->getMessage() بالإنتاج)
        echo json_encode(["success" => false, "message" => "Server error: " . $e->getMessage()]);
    }
    exit;
}
