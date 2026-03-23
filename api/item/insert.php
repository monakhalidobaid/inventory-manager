<?php
require_once '../../config/db.php';
require_once '../../config/secure_page.php';
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    echo json_encode(["success" => false, "message" => "No input received"]);
    exit;
}

$item_type = trim($data['item_type'] ?? '');
$item_code = trim($data['item_code'] ?? '');
$category_id = intval($data['category_id'] ?? 0);

$errors = [];

if (empty($item_type)) {
    $errors['item_type'] = "Please select an item type";
} elseif (!in_array($item_type, ['hardware', 'software'])) {
    $errors['item_type'] = "Invalid item type";
}

if (empty($item_code)) {
    $errors['item_code'] = "Item Code cannot be empty";
} else {
    if (!preg_match('/^OCJ_[A-Z]{2,3}_\d{3}$/', $item_code)) {
        $errors['item_code'] = "Item Code must follow format: OCJ_XX_999 or OCJ_XXX_999 (uppercase letters only)";
    }
}

if ($category_id <= 0) {
    $errors['category_id'] = "Please select a category";
}

if ($item_type === 'software') {
    $description = trim($data['description'] ?? '');
    
    if (empty($description)) {
        $errors['description'] = "Description cannot be empty";
    }
    
    if (!empty($errors)) {
        echo json_encode(["success" => false, "errors" => $errors]);
        exit;
    }
    
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    
    try {
$sql = "INSERT INTO item 
        (item_code, cat_id, item_status, description, type)
        VALUES (?, ?, 'Active', ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("siss", $item_code, $category_id, $description, $item_type);

        $stmt->execute();
        
        echo json_encode(["success" => true, "message" => "Software item added successfully"]);
        
    } catch (mysqli_sql_exception $e) {
        if ($e->getCode() == 1062) {
            echo json_encode(["success" => false, "message" => "Item code already exists. Please choose another."]);
        } else {
            echo json_encode(["success" => false, "message" => "Server error: " . $e->getMessage()]);
        }
        exit;
    }
    
} elseif ($item_type === 'hardware') {
    $purchase_date = trim($data['purchase_date'] ?? '');
    $warranty_period = trim($data['warranty_period'] ?? '');
    $description = trim($data['description'] ?? '');
    
    // Validate warranty period if provided
    if (!empty($warranty_period)) {
        if (!ctype_digit($warranty_period)) {
            $errors['warranty_period'] = "Warranty Period must contain only numbers";
        } elseif (intval($warranty_period) < 0) {
            $errors['warranty_period'] = "Warranty Period cannot be negative";
        }
    }
    
    if (!empty($errors)) {
        echo json_encode(["success" => false, "errors" => $errors]);
        exit;
    }
    
    // Calculate warranty_end and warranty_status
    $warranty_end_date = null;
    $warranty_status = 'No warranty info';
    
    // Only calculate if both purchase_date and warranty_period are provided
    if (!empty($purchase_date) && !empty($warranty_period) && intval($warranty_period) > 0) {
        try {
            $dt = new DateTime($purchase_date);
            $dt->modify('+' . intval($warranty_period) . ' months');
            $warranty_end_date = $dt->format('Y-m-d');
            
            // Calculate warranty status by comparing with today
            $today = new DateTime();
            $warranty_end = new DateTime($warranty_end_date);
            
            if ($warranty_end > $today) {
                $warranty_status = 'Under warranty';
            } else {
                $warranty_status = 'Warranty expired';
            }
            
        } catch (Exception $e) {
            echo json_encode(["success" => false, "errors" => ["purchase_date" => "Invalid purchase date"]]);
            exit;
        }
    }
    // If either purchase_date or warranty_period is missing, keep 'No warranty info'
    
    $db_status = 'Standby';
    
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    
    try {
        $sql = "INSERT INTO item
                (item_code, cat_id, purchase_date, warranty_months, warranty_end, item_status, description, type, warranty_status)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($sql);
        
        $pd = !empty($purchase_date) ? $purchase_date : null;
        $wm = !empty($warranty_period) ? intval($warranty_period) : null;
        $desc = !empty($description) ? $description : null;
        
        $stmt->bind_param(
            "sisisssss",
            $item_code,
            $category_id,
            $pd,
            $wm,
            $warranty_end_date,
            $db_status,
            $desc,
            $item_type,
            $warranty_status
        );
        
        $stmt->execute();
        
        echo json_encode(["success" => true, "message" => "Hardware item added successfully"]);
        
    } catch (mysqli_sql_exception $e) {
        if ($e->getCode() == 1062) {
            echo json_encode(["success" => false, "message" => "Item code already exists. Please choose another."]);
        } else {
            echo json_encode(["success" => false, "message" => "Server error: " . $e->getMessage()]);
        }
        exit;
    }
}
?>