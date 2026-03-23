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
    <link rel="stylesheet" href="../assets/styles/add_item.css"> 
    
</head>

<body class="department-page">
    <script>
        window.userType = '<?php echo $_SESSION['user_type'] ?? ''; ?>';
    </script>
    <header class="header">
            <h1 class="header-title">Add Item</h1>
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
        <section class="add-item-section">
            <form id="itemForm" method="post">
                <div class="add-item-form">
                    <h3 class="form-header">Add New Item</h3>
                    <hr style="border: 1px solid rgb(241, 234, 234);">
                    
                    <div class="form-grid">
                        <!-- Item Type Selection -->
                        <div class="form-group">
                            <label for="itemType" style="font-weight: 500;">Item Type</label>
                            <select id="itemType" name="itemType" required>
                                <option value="">-- Select Item Type --</option>
                                <option value="hardware">Hardware</option>
                                <option value="software">Software</option>
                            </select>
                            <span class="error-message"></span>
                        </div>

                        <!-- Item Code (Common for both) -->
                        <div class="form-group" id="itemCodeGroup" style="display: none;">
                            <label for="itemCode" style="font-weight: 500;">Item Code</label>
                            <input id="itemCode" name="itemCode" type="text" placeholder="Ex: OCJ-XX-999">
                            <span class="error-message"></span>
                        </div>

                        <!-- Category (Common for both Hardware & Software) -->
                        <div class="form-group" id="categoryGroup" style="display: none;">
                            <label for="category" style="font-weight: 500;">Category</label>
                            <select id="category" name="category">
                                <option value="">-- Select Category --</option>
                                <!-- Categories will be populated dynamically -->
                            </select>
                            <span class="error-message"></span>
                        </div>


                        <!-- Software-specific fields -->
                        <div class="form-group software-field" style="display: none;">
                            <label for="softwareDescription" style="font-weight: 500;">Description</label>
                            <input id="softwareDescription" name="softwareDescription" type="text" placeholder="Enter Description">
                            <span class="error-message"></span>
                        </div>

                        <!-- Hardware-specific fields -->

                        <div class="form-group hardware-field" style="display: none;">
                            <label for="purchaseDate" style="font-weight: 500;">Purchase Date</label>
                            <input id="purchaseDate" name="purchaseDate" type="date">
                            <span class="error-message"></span>
                        </div>

                        <div class="form-group hardware-field" style="display: none;">
                            <label for="warrantyPeriod" style="font-weight: 500;">Warranty Period (Months)</label>
                            <input id="warrantyPeriod" name="warrantyPeriod" type="number" placeholder="Enter warranty period" min="0">
                            <span class="error-message"></span>
                        </div>

                        <div class="form-group hardware-field" style="display: none;">
                            <label for="warrantyExpiry" style="font-weight: 500;">Warranty Expiry Date</label>
                            <input id="warrantyExpiry" name="warrantyExpiry" type="date" readonly>
                            <span class="error-message"></span>
                        </div>

                        <!--
                        <div class="form-group hardware-field" style="display: none;">
                            <label for="deviceStatus" style="font-weight: 500;">Device Status</label>
                            <select id="deviceStatus" name="deviceStatus">
                                <option value="">-- Select Device Status --</option>
                                <option value="out_of_service">Out of Service</option>
                                <option value="standby">Standby</option>
                                <option value="active">Active</option>
                                <option value="in_maintenance">In Maintenance</option>
                            </select>
                            <span class="error-message"></span>
                        </div>
                        -->

                        <div class="form-group hardware-field" style="display: none;">
                            <label for="hardwareDescription" style="font-weight: 500;">Description</label>
                            <input id="hardwareDescription" name="hardwareDescription" type="text" placeholder="Enter Description">
                            <span class="error-message"></span>
                        </div>
                    </div>

                    <div class="form-buttons">
                        <input type="submit" class="btn-add" value="Add">
                        <input type="reset" class="btn-cancel" value="Reset">
                    </div>
                </div>
            </form>
            <p id="result"></p>
        </section>
    </main>
<div id="toast" class="toast"></div>

    <script>
        window.addEventListener("DOMContentLoaded", () => {
            document.body.classList.add("fade-in");
        });
    </script>

    <script type="module" src="../js/add_item.js"></script>
    <script src="../js/helper.js"></script>

</body>
</html>