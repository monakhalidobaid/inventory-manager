<?php
require_once '../../config/db.php';
require_once '../../config/secure_page.php';
require_once '../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

// Read query parameters
$reportType = $_GET['reportType'] ?? '';
$from = $_GET['from'] ?? '';
$to = $_GET['to'] ?? '';

if (empty($reportType)) {
    http_response_code(400);
    die('Missing report type.');
}

// Build date filter
$dateFilter = "";
if (!empty($from) && !empty($to)) {
    $dateFilter = " AND i.purchase_date BETWEEN '$from' AND '$to' ";
}

// Prepare SQL query and column headers based on report type
$reportTitle = '';
$columnHeaders = [];

switch ($reportType) {
    case "assets_hardware":
        $reportTitle = 'Hardware Assets Report';
        $sql = "SELECT i.item_code, c.cat_name, i.purchase_date, i.warranty_months,
                       i.warranty_end, i.item_status, i.description, e.emp_name,
                       d.dept_name, i.warranty_status
                FROM item i
                JOIN categories c ON i.cat_id = c.cat_id
                LEFT JOIN employees e ON i.emp_id = e.emp_id
                LEFT JOIN departments d ON e.dept_id = d.dept_id
                WHERE i.type = 'hardware' $dateFilter";
        $columnHeaders = ["Item Code", "Category", "Purchase Date", "Warranty (Months)", 
                         "Warranty End", "Status", "Description", "Employee", 
                         "Department", "Warranty Status"];
        break;

    case "assets_software":
        $reportTitle = 'Software Assets Report';
        $sql = "SELECT i.item_code, c.cat_name, i.description
                FROM item i
                JOIN categories c ON i.cat_id = c.cat_id
                WHERE i.type = 'software' ";
        $columnHeaders = ["Item Code", "Category", "Description"];
        break;

    case "expired_warranty":
        $reportTitle = 'Expired Warranties Report';
        $sql = "SELECT i.item_code, c.cat_name, i.purchase_date, i.warranty_months,
                       i.warranty_end, i.item_status, i.description, e.emp_name,
                       d.dept_name, i.warranty_status
                FROM item i
                JOIN categories c ON i.cat_id = c.cat_id
                LEFT JOIN employees e ON i.emp_id = e.emp_id
                LEFT JOIN departments d ON e.dept_id = d.dept_id
                WHERE i.type = 'hardware' AND i.warranty_end < CURDATE() $dateFilter";
        $columnHeaders = ["Item Code", "Category", "Purchase Date", "Warranty (Months)", 
                         "Warranty End", "Status", "Description", "Employee", 
                         "Department", "Warranty Status"];
        break;

    case "under_warranty":
        $reportTitle = 'Under Warranty Report';
        $sql = "SELECT i.item_code, c.cat_name, i.purchase_date, i.warranty_months,
                       i.warranty_end, i.item_status, i.description, e.emp_name,
                       d.dept_name, i.warranty_status
                FROM item i
                JOIN categories c ON i.cat_id = c.cat_id
                LEFT JOIN employees e ON i.emp_id = e.emp_id
                LEFT JOIN departments d ON e.dept_id = d.dept_id
                WHERE i.type = 'hardware' AND i.warranty_end >= CURDATE() $dateFilter";
        $columnHeaders = ["Item Code", "Category", "Purchase Date", "Warranty (Months)", 
                         "Warranty End", "Status", "Description", "Employee", 
                         "Department", "Warranty Status"];
        break;

    default:
        http_response_code(400);
        die('Invalid report type.');
}

// Execute query
$result = $conn->query($sql);

if (!$result) {
    http_response_code(500);
    die('Query failed: ' . $conn->error);
}

// Create new Spreadsheet
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Set document properties
$spreadsheet->getProperties()
    ->setCreator("Inventory Management System")
    ->setTitle($reportTitle)
    ->setSubject($reportTitle)
    ->setDescription("Generated on " . date('Y-m-d H:i:s'));

// Add title row
$sheet->setCellValue('A1', $reportTitle);
$sheet->mergeCells('A1:' . chr(64 + count($columnHeaders)) . '1');
$sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
$sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
$sheet->getRowDimension(1)->setRowHeight(30);

// Add date range if applicable
if (!empty($from) && !empty($to)) {
    $sheet->setCellValue('A2', "Period: $from to $to");
    $sheet->mergeCells('A2:' . chr(64 + count($columnHeaders)) . '2');
    $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $headerRow = 4;
} else {
    $headerRow = 3;
}

// Add column headers
$col = 'A';
foreach ($columnHeaders as $header) {
    $sheet->setCellValue($col . $headerRow, $header);
    $sheet->getStyle($col . $headerRow)->getFont()->setBold(true);
    $sheet->getStyle($col . $headerRow)->getFill()
        ->setFillType(Fill::FILL_SOLID)
        ->getStartColor()->setARGB('FF4CAF50');
    $sheet->getStyle($col . $headerRow)->getFont()->getColor()->setARGB('FFFFFFFF');
    $sheet->getColumnDimension($col)->setAutoSize(true);
    $col++;
}

// Add data rows
$rowNum = $headerRow + 1;
while ($row = $result->fetch_assoc()) {
    $col = 'A';
    foreach ($row as $value) {
        $sheet->setCellValue($col . $rowNum, $value);
        $col++;
    }
    $rowNum++;
}

// Apply borders to all data
$lastCol = chr(64 + count($columnHeaders));
$lastRow = $rowNum - 1;
$sheet->getStyle("A$headerRow:$lastCol$lastRow")->getBorders()->getAllBorders()
    ->setBorderStyle(Border::BORDER_THIN);

// Apply alternating row colors
for ($i = $headerRow + 1; $i <= $lastRow; $i++) {
    if ($i % 2 == 0) {
        $sheet->getStyle("A$i:$lastCol$i")->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FFF5F5F5');
    }
}

// Save report history
$generatedBy = $_SESSION['user_name'] ?? $_SESSION['user_id'] ?? 'Unknown';
$stmt = $conn->prepare("INSERT INTO reports_history (report_name, report_type, generated_by) VALUES (?, ?, ?)");
$stmt->bind_param("sss", $reportTitle, $reportType, $generatedBy);
$stmt->execute();
$stmt->close();

// Set headers for download
$filename = str_replace(' ', '_', $reportTitle) . '_' . date('Y-m-d_His') . '.xlsx';
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="' . $filename . '"');
header('Cache-Control: max-age=0');

// Write file to output
$writer = new Xlsx($spreadsheet);
$writer->save('php://output');

$conn->close();
exit;
?>