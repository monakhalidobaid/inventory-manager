<?php
require_once '../../config/db.php';
require_once '../../config/secure_page.php';
header('Content-Type: application/json');

// Receive JSON input
$input = json_decode(file_get_contents('php://input'), true);

// Validate required fields
if (
    !isset($input['id'], $input['name'], $input['emp_no'], $input['status'], 
           $input['hire_date'], $input['contract_duration'], $input['dept_id']) ||
    empty(trim($input['id'])) ||
    empty(trim($input['name'])) ||
    empty(trim($input['emp_no'])) ||
    empty(trim($input['status'])) ||
    empty(trim($input['hire_date'])) ||
    empty(trim($input['contract_duration'])) ||
    empty(trim($input['dept_id']))
) {
    echo json_encode(["success" => false, "message" => "Missing or invalid data"]);
    exit;
}

$id = intval($input['id']);
$name = mysqli_real_escape_string($conn, trim($input['name']));
$emp_no = mysqli_real_escape_string($conn, trim($input['emp_no']));
$status = mysqli_real_escape_string($conn, trim($input['status']));
$hire_date = mysqli_real_escape_string($conn, trim($input['hire_date']));
$contract_duration = intval($input['contract_duration']);
$dept_id = intval($input['dept_id']);

// Validate status enum
if (!in_array($status, ['employed', 'not employed'])) {
    echo json_encode(["success" => false, "message" => "Invalid status value"]);
    exit;
}

// Calculate contract end date
$hire_date_obj = new DateTime($hire_date);
$hire_date_obj->modify("+{$contract_duration} years");
$contract_end_date = $hire_date_obj->format('Y-m-d');

// Check if employee number already exists for another employee
$checkSql = "SELECT emp_id FROM employees WHERE emp_no = '$emp_no' AND emp_id != $id";
$checkRes = mysqli_query($conn, $checkSql);

if (mysqli_num_rows($checkRes) > 0) {
    echo json_encode(["success" => false, "message" => "Employee number already exists"]);
    exit;
}

// Check if department exists
$deptCheckSql = "SELECT dept_id FROM departments WHERE dept_id = $dept_id";
$deptCheckRes = mysqli_query($conn, $deptCheckSql);

if (mysqli_num_rows($deptCheckRes) === 0) {
    echo json_encode(["success" => false, "message" => "Invalid department selected"]);
    exit;
}

// Perform update
$updateSql = "UPDATE employees 
              SET emp_name='$name', 
                  emp_no='$emp_no', 
                  status='$status', 
                  hire_date='$hire_date', 
                  contract_end_date='$contract_end_date', 
                  dept_id=$dept_id
              WHERE emp_id=$id";

if (mysqli_query($conn, $updateSql)) {
    echo json_encode(["success" => true, "message" => "Employee updated successfully"]);
} else {
    echo json_encode(["success" => false, "message" => "Update failed: " . mysqli_error($conn)]);
}
?>