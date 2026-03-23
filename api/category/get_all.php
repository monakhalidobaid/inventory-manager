<?php
require_once '../../config/db.php';
require_once '../../config/secure_page.php';
header('Content-Type: application/json');

$sql = "SELECT cat_id, cat_name 
        FROM categories" ;   
$result = mysqli_query($conn, $sql);

$category = [];
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $category[] = $row;
    }
    echo json_encode(["success" => true, "category" => $category]);
} else {
    echo json_encode(["success" => false, "message" => "Database error"]);
}
