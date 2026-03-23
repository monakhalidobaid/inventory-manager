const form = document.getElementById('employeerForm');
const tbody = document.querySelector('#emploeeyTable tbody');
const searchInput = document.getElementById('searchInput');
const result = document.getElementById('result');

const employeeName = document.getElementById("employeeName");
const employeeNo = document.getElementById("employeeNo");
const empStatus = document.getElementById("status");
const hireDate = document.getElementById("hireDate");
const contract = document.getElementById("contract");
const department = document.getElementById("department");

// Edit modal elements
const editModal = document.getElementById("editModal");
const editForm = document.getElementById("editEmployeeForm");
const editName = document.getElementById("editname");
const editEmployeeNo = document.getElementById("editEmployeeNo");
const editStatus = document.getElementById("editStatus");
const editHireDate = document.getElementById("editHireDate");
const editContract = document.getElementById("editContract");
const editDepartment = document.getElementById("editDepartment");
const editContractEnd = document.getElementById("editContractEnd");
let currentEmployeetId = null;

// Delete modal elements
const deleteModal = document.getElementById("deleteModal");
const confirmDeleteBtn = document.getElementById("confirmDelete");
const cancelDeleteBtn = document.getElementById("cancelDelete");
let employeeIdToDelete = null;

let currentPage = 1;
let limit = 5;


// ==== Pagination ====
function renderPagination(total, page, limit) {
  const totalPages = Math.ceil(total / limit);
  const pagination = document.getElementById("pagination");
  if (!pagination) return;

  pagination.innerHTML = "";

  for (let i = 1; i <= totalPages; i++) {
    const btn = document.createElement("button");
    btn.textContent = i;
    if (i === page) btn.classList.add("active");

    btn.addEventListener("click", () => {
      currentPage = i;
      fetchEmploeey();
    });

    pagination.appendChild(btn);
  }
}
async function loadDepartments() {
    try {
        const res = await fetch('../api/department/get_active.php');
        const data = await res.json();

        if (data.success) {
            department.innerHTML = '<option value="">-- Select Employee Department --</option>';
            data.departments.forEach(dep => {
                const opt = document.createElement('option');
                opt.value = dep.dept_id;
                opt.textContent = dep.dept_name;
                department.appendChild(opt);
            });
        } else {
            showToast("Failed to load departments", "error");
        }
    } catch (err) {
        console.error(err);
        showToast("Error loading departments", "error");
    }
}

// ==== Validation helpers ====
function showFieldError(field, msg) {
    const span = field.parentElement.querySelector('.error-message');
    if (span) { 
        span.textContent = msg;
        span.classList.add("show");
    }
    field.classList.add("input-error");
}

function clearErrors() {
    document.querySelectorAll("#employeerForm .error-message").forEach(span => {
        span.textContent = "";
        span.classList.remove("show"); 
    });
    document.querySelectorAll("#employeerForm input, #employeerForm select").forEach(input => {
        input.classList.remove("input-error");
    });
}

function validateEmployeeForm() {
    const employeeNameVal = employeeName.value.trim();
    const employeeNoVal = employeeNo.value.trim();
    const statusVal = empStatus.value;
    const hireDateVal = hireDate.value;
    const contractVal = contract.value;
    const departmentVal = department.value;
    
    let isValid = true;
    clearErrors();
    
    if (!employeeNameVal) { 
        showFieldError(employeeName, "Employee name is required"); 
        isValid = false;
    } else {
        const nameRegex = /^[\u0600-\u06FFa-zA-Z\s]+$/;
        if (!nameRegex.test(employeeNameVal)) { 
            showFieldError(employeeName, "Name must contain only Arabic or English letters"); 
            isValid = false;
        }  
    }
    
    if (!employeeNoVal) {
        showFieldError(employeeNo, "Employee number is required");
        isValid = false;
    } else if (employeeNoVal.length < 4) {
        showFieldError(employeeNo, "Employee number must be at least 4 characters");
        isValid = false;
    }
    
    if (statusVal === "") { 
        showFieldError(empStatus, "Please select employee status"); 
        isValid = false;
    }
    
    if (hireDateVal === "") { 
        showFieldError(hireDate, "Please select employee hire date"); 
        isValid = false;
    }
    
    if (!contractVal || parseInt(contractVal) < 1) { 
        showFieldError(contract, "Contract duration must be at least 1 year"); 
        isValid = false;
    }
    
    if (departmentVal === "") { 
        showFieldError(department, "Please select a department"); 
        isValid = false;
    }
    
    return isValid;
}

// ==== Edit modal error handling ====
function showEditFieldError(field, msg) {
  const span = field.parentElement.querySelector('.error-message');
  if (span) {
    span.textContent = msg;
    span.classList.add("show");
  }
  field.classList.add("input-error");
}

function clearEditErrors() {
  editForm.querySelectorAll('.error-message').forEach(span => {
    span.textContent = "";
    span.classList.remove("show");
  });
  editForm.querySelectorAll('input, select').forEach(input => {
    input.classList.remove("input-error");
  });
}

// Load departments into edit modal
async function loadEditDepartments() {
    try {
        const res = await fetch('../api/department/get_active.php');
        const data = await res.json();

        if (data.success) {
            editDepartment.innerHTML = '<option value="">-- Select Employee Department --</option>';
            data.departments.forEach(dep => {
                const opt = document.createElement('option');
                opt.value = dep.dept_id;
                opt.textContent = dep.dept_name;
                editDepartment.appendChild(opt);
            });
        }
    } catch (err) {
        console.error(err);
    }
}

// ==== Render Employee ====
function displayEmployee(employees) {
    tbody.innerHTML = '';
    
    employees.forEach(employee => {
        const tr = document.createElement('tr');

        const tdId = document.createElement('td'); tdId.textContent = employee.emp_id;
        const tdName = document.createElement('td'); tdName.textContent = employee.emp_name;
        const tdNo = document.createElement('td'); tdNo.textContent = employee.emp_no;
        const tdStatus = document.createElement('td'); tdStatus.textContent = employee.status;
        const tdhire = document.createElement('td'); tdhire.textContent = employee.hire_date;
        const tdcontract = document.createElement('td'); tdcontract.textContent = employee.contract_end_date;
        const tddept = document.createElement('td'); tddept.textContent = employee.dept_name;

        const tdActions = document.createElement('td');
        if (window.userType === 'admin') {
            const dropdown = document.createElement('div');
            dropdown.classList.add('dropdown');
            const btn = document.createElement('button');
            btn.classList.add('dropdown-btn'); btn.textContent = '⋮';
            const menu = document.createElement('ul');
            menu.classList.add('dropdown-menu');

            const liEdit = document.createElement('li');
            const editLink = document.createElement('a');
            editLink.href = '#'; editLink.classList.add('edit'); 
            editLink.innerHTML = '<span class="material-symbols-outlined">edit</span>Edit';
            liEdit.appendChild(editLink);

            const liDelete = document.createElement('li');
            const delLink = document.createElement('a');
            delLink.href = '#'; delLink.classList.add('delete'); 
            delLink.innerHTML = '<span class="material-symbols-outlined">delete</span>Delete';
            liDelete.appendChild(delLink);

            menu.append(liEdit, liDelete);
            dropdown.append(btn, menu);
            tdActions.appendChild(dropdown);
        }

        tr.append(tdId, tdName, tdNo, tdStatus, tdhire, tdcontract, tddept, tdActions);
        tbody.appendChild(tr);
    });
}

// ==== Fetch Employee ====
async function fetchEmploeey() {
    const q = searchInput.value.trim();
    try {
        const res = await fetch(`../api/employees/get.php?q=${encodeURIComponent(q)}&page=${currentPage}&limit=${limit}`);
        const data = await res.json();
        if (data.success) {
            displayEmployee(data.employees);
            renderPagination(data.total, data.page, data.limit);
        }
    } catch (err) {
        console.error(err);
        showToast("Error fetching employees", "error");
    }
}

// ==== Search ====
searchInput.addEventListener('input', debounce(()=>{ currentPage=1; fetchEmploeey(); }, 300));

// ==== Form submit ====
if (form) {
    form.addEventListener('submit', async e => {
        e.preventDefault();
        if(!validateEmployeeForm()) {
            return;
        }

        const payload = {
            employee_name: employeeName.value.trim(),
            employee_no: employeeNo.value.trim(),
            emp_status: empStatus.value,
            hire_date: hireDate.value,
            contract_d: contract.value,
            department: department.value
        };

        try {
            const res = await fetch('../api/employees/insert.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify(payload)
            });
            const data = await res.json();
            
            if (data.success) {
                showToast(data.message, 'success');
                form.reset();
                clearErrors();
                document.getElementById("contractEnd").value = "";
                fetchEmploeey();
            } else {
                if (data.errors) {
                    Object.keys(data.errors).forEach(key => {
                        const fieldMap = {
                            'employee_name': employeeName,
                            'employee_no': employeeNo,
                            'emp_status': empStatus,
                            'hire_date': hireDate,
                            'contract_d': contract,
                            'department': department
                        };
                        if (fieldMap[key]) {
                            showFieldError(fieldMap[key], data.errors[key]);
                        }
                    });
                    showToast("Please fix the errors", "error");
                } else {
                    showToast(data.message || "Error occurred", 'error');
                }
            }
        } catch(err) {
            console.error("Fetch error:", err);
            showToast("Server error", 'error');
        }
    });
}

// Calculate contract end date
function calculateContractEnd() {
    const hire = hireDate.value;
    const years = parseInt(contract.value);
    if (hire && years > 0) {
        const d = new Date(hire);
        d.setFullYear(d.getFullYear() + years);
        const endDate = d.toISOString().split("T")[0];
        document.getElementById("contractEnd").value = endDate;
        return endDate;
    }
    document.getElementById("contractEnd").value = "";
    return null;
}

hireDate.addEventListener("change", calculateContractEnd);
contract.addEventListener("input", calculateContractEnd);

// Calculate contract end date for edit modal
function calculateEditContractEnd() {
    const hire = editHireDate.value;
    const years = parseInt(editContract.value);
    if (hire && years > 0) {
        const d = new Date(hire);
        d.setFullYear(d.getFullYear() + years);
        const endDate = d.toISOString().split("T")[0];
        editContractEnd.value = endDate;
        return endDate;
    }
    editContractEnd.value = "";
    return null;
}

editHireDate.addEventListener("change", calculateEditContractEnd);
editContract.addEventListener("input", calculateEditContractEnd);

// ==== Dropdown + Edit + Delete actions ====
document.addEventListener('click', async function(e) {
    // Dropdown toggle
    if (e.target.closest('.dropdown-btn')) {
        e.preventDefault();
        document.querySelectorAll('.dropdown.open').forEach(d => d.classList.remove('open'));
        e.target.closest('.dropdown').classList.add('open');
        return;
    } 

    if (!e.target.closest('.dropdown')) {
        document.querySelectorAll('.dropdown.open').forEach(d => d.classList.remove('open'));
    }

    if (e.target.closest('.dropdown-menu a')) {
        e.target.closest('.dropdown').classList.remove('open');
    }

    // Edit button
    if (e.target.closest(".edit")) {
        e.preventDefault();

        let row = e.target.closest("tr");
        currentEmployeetId = parseInt(row.cells[0].textContent.trim(), 10);
        let NAME = row.cells[1].textContent;
        let No = row.cells[2].textContent;
        let STATUS = row.cells[3].textContent;
        let HireDate = row.cells[4].textContent;
        let ContractEnd = row.cells[5].textContent;
        let DepartmentName = row.cells[6].textContent;

        await loadEditDepartments();

        editName.value = NAME;
        editEmployeeNo.value = No;
        editStatus.value = STATUS;
        editHireDate.value = HireDate;
        editContractEnd.value = ContractEnd;

        if (HireDate && ContractEnd) {
            const start = new Date(HireDate);
            const end = new Date(ContractEnd);
            const years = Math.round((end - start) / (365.25 * 24 * 60 * 60 * 1000));
            editContract.value = years;
        }

        Array.from(editDepartment.options).forEach(opt => {
            if (opt.textContent === DepartmentName) {
                editDepartment.value = opt.value;
            }
        });

        editModal.style.display = "block";
        setTimeout(() => editModal.classList.add("show"), 10);
        editModal.scrollIntoView({ behavior: "smooth", block: "start" });
        return;
    }

    // Close edit modal
    if (e.target.classList.contains("close-btn")) {
        editModal.style.display = "none";
        clearEditErrors();
        return;
    }

    // Delete button
    if (e.target.closest(".delete")) {
        e.preventDefault();
        let row = e.target.closest("tr");
        employeeIdToDelete = parseInt(row.cells[0].textContent.trim(), 10);
        deleteModal.style.display = "block";
        setTimeout(() => deleteModal.classList.add("show"), 10);
        deleteModal.scrollIntoView({ behavior: "smooth", block: "start" });
        return;
    }
});

// Close modals when clicking outside
window.addEventListener("click", function(e) {
    if (e.target === editModal) {
        editModal.style.display = "none";
        clearEditErrors();
    }
    if (e.target === deleteModal) {
        deleteModal.style.display = "none";
        employeeIdToDelete = null;
    }
});

// ==== Edit form submission ====
editForm.addEventListener('submit', async function(e) {
    e.preventDefault();
    clearEditErrors();

    let nameValue = editName.value.trim();
    let employeeNoValue = editEmployeeNo.value.trim();
    let statusValue = editStatus.value;
    let hireDateValue = editHireDate.value;
    let contractValue = editContract.value;
    let departmentValue = editDepartment.value;

    let isValid = true;

    if (!nameValue) {
        showEditFieldError(editName, "Employee name is required");
        isValid = false;
    } else {
        const nameRegex = /^[\u0600-\u06FFa-zA-Z\s]+$/;
        if (!nameRegex.test(nameValue)) {
            showEditFieldError(editName, "Name must contain only Arabic or English letters");
            isValid = false;
        }
    }

    if (!employeeNoValue) {
        showEditFieldError(editEmployeeNo, "Employee number is required");
        isValid = false;
    } else if (employeeNoValue.length < 4) {
        showEditFieldError(editEmployeeNo, "Employee number must be at least 4 characters");
        isValid = false;
    }

    if (statusValue === "") {
        showEditFieldError(editStatus, "Please select employee status");
        isValid = false;
    }

    if (hireDateValue === "") {
        showEditFieldError(editHireDate, "Please select employee hire date");
        isValid = false;
    }

    if (!contractValue || parseInt(contractValue) < 1) {
        showEditFieldError(editContract, "Contract duration must be at least 1 year");
        isValid = false;
    }

    if (departmentValue === "") {
        showEditFieldError(editDepartment, "Please select a department");
        isValid = false;
    }

    if (!isValid) return;

    let updatedData = {
        id: currentEmployeetId,
        name: nameValue,
        emp_no: employeeNoValue,
        status: statusValue,
        hire_date: hireDateValue,
        contract_duration: contractValue,
        dept_id: departmentValue
    };

    try {
        const response = await fetch("../api/employees/update.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify(updatedData)
        });

        const data = await response.json();

        if (data.success) {
            showToast(data.message, "success");
            await fetchEmploeey();
            editModal.style.display = "none";
            clearEditErrors();
        } else {
            if (data.message.toLowerCase().includes("employee number")) {
                showEditFieldError(editEmployeeNo, data.message);
            } else {
                showToast(data.message, "error");
            }
        }
    } catch (err) {
        console.error("Error updating employee:", err);
        showEditFieldError(editName, "Error connecting to server");
    }
});

// ==== Delete functionality ====
cancelDeleteBtn.addEventListener("click", () => {
    deleteModal.style.display = "none";
    employeeIdToDelete = null;
});

confirmDeleteBtn.addEventListener("click", async () => {
    if (!employeeIdToDelete) return;

    try {
        const response = await fetch("../api/employees/delete.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ id: employeeIdToDelete })
        });

        const data = await response.json();

        if (data.success) {
            showToast(data.message, "success");
            await fetchEmploeey();
        } else {
            showToast(data.message, "error");
        }
    } catch (err) {
        console.error("Error deleting employee:", err);
        showToast("Error connecting to server", "error");
    }

    deleteModal.style.display = "none";
    employeeIdToDelete = null;
});

// ==== Initial load ====
document.addEventListener('DOMContentLoaded', () => {
    loadDepartments();
    fetchEmploeey();
});