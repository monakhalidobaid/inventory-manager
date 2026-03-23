<?php
include_once '../config/secure_page.php'; 
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Manager</title>
    <link rel="stylesheet" href="../assets/styles/employees.css">
    <link rel="stylesheet" href="../assets/styles/pagination.css">    
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" />
    <link rel="stylesheet" href="../assets/styles/main.css">

</head>
<body class="department-page">
    <script>
        window.userType = '<?php echo $_SESSION['user_type'] ?? ''; ?>';
    </script>
    <header class="header">
            <h1 class="header-title">Employees</h1>
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
<section class="add-employee-section">
    <form id="employeerForm" method="post">
        <div class="add-employee-form">
        <h3 class="form-header">Add New Employee</h3>
        <hr style="border: 1px solid rgb(241, 234, 234);">
        <div class="form-grid">
            <div class="form-group">
                <label for="employeeName" style="font-weight: 500;">Name</label>
                <input id="employeeName" name="employeeName" type="text" placeholder="Enter Employee Name" required>
                <span class="error-message"></span>
            </div>

            <div class="form-group">
                <label for="employeeNo" style="font-weight: 500;">Employee Number</label>
                <input id="employeeNo" name="employeeNo" type="text" placeholder="Enter Employee Number" required>
                <span class="error-message"></span>
            </div>

            <div class="form-group">
                <label for="status" style="font-weight: 500;">Employee Status</label>
                <select id="status" name="status" required>
                    <option value="">-- Select Employee Status --</option>
                    <option value="employed">Employed</option>
                    <option value="not employed">Not Employed</option>
                </select>
                <span class="error-message"></span>
            </div>

            <div class="form-group">
                <label for="hireDate" style="font-weight: 500;">Hire Date</label>
                <input id="hireDate" name="hireDate" type="date" required>
                <span class="error-message"></span>
            </div>

            <div class="form-group">
                <label for="contract" style="font-weight: 500;">Contract Duration</label>
                <input id="contract" name="contract" type="number" placeholder="Contract Duration in Years"  min="1" required>
                <span class="error-message"></span>
            </div>

            <div class="form-group">
                <label for="department" style="font-weight: 500;">Department</label>
                <select id="department" name="department" required>
                    <option value="">-- Select Employee Department --</option>
                    <option></option>
                    <option></option>
                </select>
                <span class="error-message"></span>
            </div>

            <div class="form-group">
                <label for="contractEnd">Contract End Date</label>
                <input id="contractEnd" type="text" readonly>
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

            <section class="departments-list-section">
            <div class="section-header">
                <h3 class="section-title">Employees</h3>
                <div class="search-container">
                <input type="search" placeholder="Search" class="search-input" id="searchInput">
                </div>
            </div>

            <table class="transactions-table"  id="emploeeyTable">
                <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Employee No</th>
                    <th>Status</th>
                    <th>Hire Date</th>
                    <th>Contract End Date</th>
                    <th>Department</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                     <!-- البيانات ستضاف هنا -->
                </tbody>
            </table>
                <div id="pagination"></div>

            </section>
    </main>

<!-- Edit User Modal -->
<div id="editModal" class="modal">
  <div class="modal-content">
    <span class="close-btn">&times;</span>
    <h3 class="form-header">Edit Employee</h3>
    <hr style="border: 1px solid rgb(241, 234, 234);">

    <form id="editEmployeeForm">
      <div class="form-group">
        <label for="editname">Name</label>
        <input type="text" id="editname" name="editname" required>
        <span class="error-message"></span>
      </div>

    <div class="form-group">
        <label for="editEmployeeNo">Employee Number</label>
        <input type="text" id="editEmployeeNo" name="editEmployeeNo" required>
        <span class="error-message"></span>
      </div>

       <div class="form-group">
            <label for="editStatus" style="font-weight: 500;">Employee Status</label>
            <select id="editStatus" name="editStatus" required>
                <option value="">-- Select Employee Status --</option>
                <option value="employed">Employed</option>
                <option value="not employed">Not Employed</option>
                <span class="error-message"></span>
            </select>
        </div>

             <div class="form-group">
                <label for="editHireDate" style="font-weight: 500;">Hire Date</label>
                <input id="editHireDate" name="editHireDate" type="date" required>
                <span class="error-message"></span>

            </div>

            <div class="form-group">
                <label for="editContract" style="font-weight: 500;">Contract Duration</label>
                <input id="editContract" name="editContract" type="number" placeholder="Contract Duration in Years"  min="1" required>
                <span class="error-message"></span>

            </div>

            <div class="form-group">
                <label for="editDepartment" style="font-weight: 500;">Department</label>
                <select id="editDepartment" name="editDepartment" required>
                    <option value="">-- Select Employee Department --</option>
                    <option></option>
                    <option></option>
                </select>
                <span class="error-message"></span>
            </div>

            <div class="form-group">
                <label for="editContractEnd">Contract End Date</label>
                <input id="editContractEnd" type="text" readonly>
                <span class="error-message"></span>
            </div>

            
      <div class="form-buttons">
        <button type="submit" class="btn-add">Edit</button>
      </div>
        </div>

    </form>
  </div>
</div>


    
<!--  رسالة نجاح عملية التحديث او الحذف-->
<div id="toast" class="toast"></div>

<!-- Delete Employee Modal -->
<div id="deleteModal" class="modal">
  <div class="modal-content">
    <h3 class="form-header">⚠️ Delete Employee</h3>
    <hr style="border: 1px solid rgb(241, 234, 234);">
    <p>Are you sure you want to delete this employee? 
       <br>This action is irreversible and the data cannot be recovered.</p>
    <div class="form-buttons">
      <button id="confirmDelete" class="btn-add">Delete</button>
      <button id="cancelDelete" class="btn-cancel">Cancel</button>
    </div>
  </div>
</div>

        <script>
  window.addEventListener("DOMContentLoaded", () => {
    document.body.classList.add("fade-in");
  });
</script>
    <script src="../js/helper.js"></script>
    <script type="module" src="../js/employee.js"></script>



</body>
</html>