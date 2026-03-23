<?php
require_once '../../config/db.php';
require_once '../../config/secure_page.php';
require_once '../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// Read query parameters
$reportType = $_GET['reportType'] ?? '';
$from = $_GET['from'] ?? '';
$to = $_GET['to'] ?? '';

if (empty($reportType)) {
    http_response_code(400);
    header('Content-Type: text/plain; charset=utf-8');
    die("Missing report type.");
}

// Prepare SQL query based on report type
switch ($reportType) {
    case "assets_hardware":
        $sql = "SELECT i.item_code, c.cat_name, i.purchase_date, i.warranty_months, 
                       i.warranty_end, i.item_status, i.description, e.emp_name, 
                       d.dept_name, i.warranty_status
                FROM item i
                JOIN categories c ON i.cat_id = c.cat_id
                LEFT JOIN employees e ON i.emp_id = e.emp_id
                LEFT JOIN departments d ON e.dept_id = d.dept_id
                WHERE i.type = 'hardware' ";
        
        if (!empty($from) && !empty($to)) {
            $sql .= " AND (i.purchase_date BETWEEN '$from' AND '$to' OR i.purchase_date IS NULL) ";
        }
        
        $reportName = "Hardware Assets Report";
        $headers = ["Item Code","Category","Purchase Date","Warranty (Months)","Warranty End","Status","Description","Employee","Department","Warranty Status"];
        break;

    case "assets_software":
        $sql = "SELECT i.item_code, c.cat_name, i.description
                FROM item i
                JOIN categories c ON i.cat_id = c.cat_id
                WHERE i.type = 'software' ";
        
        $reportName = "Software Assets Report";
        $headers = ["Item Code","Category","Description"];
        break;

    default:
        http_response_code(400);
        header('Content-Type: text/plain; charset=utf-8');
        die("Invalid report type.");
}

// Execute query
$result = $conn->query($sql);
if (!$result) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    die("Query failed: " . $conn->error);
}

// Fetch data
$rows = [];
while ($row = $result->fetch_assoc()) {
    $rows[] = array_values($row);
}

// ===== التحقق من وجود بيانات قبل أي شيء =====
if (count($rows) === 0) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    die("No data found for the selected criteria.");
}

// الآن نبدأ نسوي Excel لأن البيانات موجودة
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Set title
$sheet->setCellValue('A1', $reportName);
$sheet->mergeCells('A1:' . chr(65 + count($headers) - 1) . '1');
$sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
$sheet->getStyle('A1')->getAlignment()->setHorizontal('center');

// Set period if dates exist
if (!empty($from) && !empty($to)) {
    $sheet->setCellValue('A2', "Period: $from to $to");
    $sheet->mergeCells('A2:' . chr(65 + count($headers) - 1) . '2');
    $sheet->getStyle('A2')->getAlignment()->setHorizontal('center');
    $headerRow = 4;
} else {
    $headerRow = 3;
}

// Set headers
$col = 'A';
foreach ($headers as $header) {
    $sheet->setCellValue($col . $headerRow, $header);
    $sheet->getStyle($col . $headerRow)->getFont()->setBold(true);
    $sheet->getStyle($col . $headerRow)->getFill()
        ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
        ->getStartColor()->setRGB('4CAF50');
    $col++;
}

// Fill data
$rowNum = $headerRow + 1;
foreach ($rows as $row) {
    $col = 'A';
    foreach ($row as $cell) {
        $sheet->setCellValue($col . $rowNum, $cell);
        $col++;
    }
    $rowNum++;
}

// Auto-size columns
foreach (range('A', chr(65 + count($headers) - 1)) as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
}

// Output file - نحط الـ headers هنا فقط لما نكون متأكدين إن في بيانات
$filename = str_replace(' ', '_', $reportName) . '_' . date('Y-m-d') . '.xlsx';
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="' . $filename . '"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
?>