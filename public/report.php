<?php
include_once '../config/secure_page.php'; 
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Manager</title>
    <link rel="stylesheet" href="../assets/styles/report.css">  
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" />
    <link rel="stylesheet" href="../assets/styles/main.css">

</head>
<body class="department-page">
      <script>
        window.userType = '<?php echo $_SESSION['user_type'] ?? ''; ?>';
    </script>
    <header class="header">
            <h1 class="header-title">Report Generation</h1>
            <a href="../api/user/logout.php" class="logout-btn">Logout</a>
    </header>

   <aside class="sidebar">
        <nav class="sidebar-nav">
            <ul class="nav-list primary-nav">
                <li class="nav-item">
                    <a href="dashboard.php" class="nav-link">
                        <span class="nav-icon material-symbols-outlined">analytics</span>
                        <span class="nav-label">Dashboard</span>
                    </a>
                </li> 
            <?php if ($_SESSION['user_type'] === 'admin'): ?>
                 <li class="menu-item">
                    <a href="user.php" class="nav-link">
                    <span class="material-symbols-outlined">person_apron</span> 
                    <span class="nav-label">Users</span>
                    </a>
                </li>                 
            <?php endif; ?>

                <li class="menu-item">
                    <a href="department.php" class="nav-link">
                    <span class="material-symbols-outlined">corporate_fare</span> 
                    <span class="nav-label">Departments</span>
                    </a>
                </li>                 

            <?php if ($_SESSION['user_type'] === 'admin'): ?>                
                <li class="menu-item">
                    <a href="employees.php" class="nav-link">
                    <span class="nav-icon material-symbols-outlined">badge</span>
                    <span class="nav-label">Employees</span>
                    </a>
                </li> 
            <?php endif; ?>
                                <li class="menu-item">

                    <a href="categories.php" class="nav-link">
                        <span class="nav-icon material-symbols-outlined">category</span>
                        <span class="nav-label">Categories</span>
                    </a>
                </li>  
                
                <li class="menu-item">
                    <a href="item.php" class="nav-link">
                        <span class="nav-icon material-symbols-outlined">list_alt</span>
                        <span class="nav-label">Items</span>
                    </a>
                </li> 

            <?php if ($_SESSION['user_type'] === 'admin'): ?>                
                <li class="menu-item">
                    <a href="add_item.php" class="nav-link">
                        <span class="nav-icon material-symbols-outlined">list_alt_add</span>
                        <span class="nav-label">Add Item</span>
                    </a>
                </li> 
            <?php endif; ?>

            <?php if ($_SESSION['user_type'] === 'admin'): ?>   
                <li class="menu-item">
                    <a href="transaction.php" class="nav-link">
                        <span class="nav-icon material-symbols-outlined">flowsheet</span>
                        <span class="nav-label">Transaction</span>
                    </a>
                </li> 
                            <?php endif; ?>


                <li class="menu-item">
                    <a href="report.php" class="nav-link">
                        <span class="nav-icon material-symbols-outlined">content_paste</span>
                        <span class="nav-label">Reports</span>
                    </a>
                </li> 

            </ul> 
        </nav>        
    </aside>
<main class="main-content">
        <div class="content-grid">
            <!-- Sidebar with Report Types -->
            <aside class="reports-sidebar">
                <h3 class="sidebar-title">
                    <span class="material-symbols-outlined">folder_open</span>
                    Report Types
                </h3>

                <!-- Assets Reports -->
                <div class="report-category">
                    <div class="category-title">📋 Assets Reports</div>
                    <div class="report-option active" data-report="assets_hardware">
                        <span class="material-symbols-outlined"> jamboard_kiosk </span>
                        Hardware Assets
                    </div>
                    <div class="report-option" data-report="assets_software">
                        <span class="material-symbols-outlined">code_blocks</span>
                        Software Assets
                    </div>
                </div>
                <!-- Warranty Reports -->
                <div class="report-category">
                    <div class="category-title">⏰ Warranty Reports</div>
                    <div class="report-option" data-report="expired_warranty">
                        <span class="material-symbols-outlined">event_busy</span>
                        Expired Warranties
                    </div>
                    <div class="report-option" data-report="under_warranty">
                        <span class="material-symbols-outlined">verified</span>
                        Under Warranty
                    </div>
                </div>

            

            </aside>

            <!-- Main Content Area -->
            <div class="report-content">
                <!-- Filters Section -->
                <div class="filters-section">
                    <div class="filters-title">
                        <span class="material-symbols-outlined">filter_alt</span>
                        Purchase Date Filter
                    </div>
                    <div class="filters-grid">
                        <div class="filter-group">
                            <label>Date From</label>
                            <input type="date" value="">
                        </div>
                        <div class="filter-group">
                            <label>Date To</label>
                            <input type="date" value="">
                        </div>
                       
                    </div>
                    <div class="filter-actions">
                        <button class="btn-add">
                            <span class="material-symbols-outlined">refresh</span>
                            Reset
                        </button>
                        <button class="btn-add">
                                <span class="material-symbols-outlined">table_chart</span>
                                Export to Excel
                            </button>

                    </div>
                </div>
                <section class="recent-transactions">
                                <!-- مثال للتصميم - سيتم حذفه واستبداله بالبيانات الحقيقية -->
                            <h3 class="section-title">Recent Report</h3>
                            <table class="transactions-table" border="1" id="itemTable">
                            <thead>
                                <tr>
                                    <th>Report Type</th>
                                    <th>Generation Date</th>
                                    <th>Generated by</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- البيانات ستضاف هنا -->
                            </tbody>
                            </table>
                        </section>
            </div>
        </div>
    </main>


<!--  رسالة نجاح عملية التحديث او الحذف-->
<div id="toast" class="toast"></div>

    <script>
  window.addEventListener("DOMContentLoaded", () => {
    document.body.classList.add("fade-in");
  });
    </script>
    <script type="module" src="/inventory_manager/js/report.js"></script>
    <script src="../js/helper.js"></script>

</body>
</html>