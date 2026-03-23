<?php
include_once '../config/secure_page.php'; 
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Manager</title>
    <link rel="stylesheet" href="../assets/styles/pagination.css">    
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" />
    <link rel="stylesheet" href="../assets/styles/main.css">
    <link rel="stylesheet" href="../assets/styles/transaction.css">

</head>
<body class="department-page">
      <script>
        window.userType = '<?php echo $_SESSION['user_type'] ?? ''; ?>';
    </script>
    <header class="header">
            <h1 class="header-title">Transaction</h1>
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

        <section class="departments-list-section">
             <div class="filters-container">
                    <input type="search" placeholder="Search by ID, item code or user..." class="search-input" id="searchInput">
                    
                    <select class="filter-select" id="tranTypeFilter">
                        <option value="">All</option>
                        <option value="assign">Assign</option>
                         <option value="edit">Edit</option>
                        <option value="transfer">Transfer</option>
                        <option value="return">Return</option>
                        <option value="changeStatus">Change Status</option>       
                    </select>

                    <button id="resetFiltersBtn" class="reset-btn" title="Clear Filters">
                        <span class="material-symbols-outlined">filter_alt_off</span>
                    </button>

                </div>
            </div>

            <table class="transactions-table"  id="itemTable">
                <thead>
                <tr>
                    <th>ID</th>
                    <th>Item ID</th>
                    <th>Item Code</th> 
                    <th>Transaction Type</th>
                    <th>Transaction Date</th>
                    <th>Created by</th>
                </tr>
                </thead>
                <tbody>
                     <!-- البيانات ستضاف هنا -->
                </tbody>
            </table>
                <div id="pagination"></div>

        </section>
    </main>

     

    <!-- read item Modal -->
<div id="readModal" class="modal">
  <div class="modal-content">
    <span class="close-btn">&times;</span>
    <h3 class="form-header">Item </h3>
    <hr style="border: 1px solid rgb(241, 234, 234);">

    
  </div>
</div>

<!-- Modal container -->
<div id="modal" class="modal" aria-hidden="true" role="dialog" aria-modal="true"></div>

   
<!--  رسالة نجاح عملية التحديث او الحذف-->
<div id="toast" class="toast"></div>



    <script>
  window.addEventListener("DOMContentLoaded", () => {
    document.body.classList.add("fade-in");
  });
    </script>
    <script type="module" src="../js/transaction.js"></script>
    <script src="../js/helper.js"></script>

</body>
</html>