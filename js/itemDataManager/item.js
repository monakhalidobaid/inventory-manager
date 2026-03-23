import { buildEditModal } from './modalBuilders/buildEditModal.js';
import { buildAssignModal } from './modalBuilders/buildAssignModal.js';
import { buildReturnModal } from './modalBuilders/buildReturnModal.js';
import { buildTransferModal } from './modalBuilders/buildTransferModal.js';
import { buildChangeStatusModal } from './modalBuilders/buildChangeStatusModal.js';

const tbody = document.querySelector('#itemTable tbody');
const searchInput = document.getElementById('searchInput');
const departmentFilter = document.getElementById('departmentFilter');
const categoryFilter = document.getElementById('categoryFilter');
const statusFilter = document.getElementById('statusFilter');
const typeFilter = document.getElementById('typeFilter');
const resetFiltersBtn = document.getElementById('resetFiltersBtn');

departmentFilter.addEventListener('change', () => {
    currentPage = 1;
    fetchItem();
});

categoryFilter.addEventListener('change', () => {
    currentPage = 1;
    fetchItem();
});

statusFilter.addEventListener('change', () => {
    currentPage = 1;
    fetchItem();
});

typeFilter.addEventListener('change', () => {
    currentPage = 1;
    fetchItem();
});

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
      fetchItem();
    });

    pagination.appendChild(btn);
  }
}

async function loadDepartments() {
    try {
        const res = await fetch('/inventory_manager/api/department/get_active.php');
        const data = await res.json();
        if (data.success) {
            data.departments.forEach(dep => {
                const opt = document.createElement('option');
                opt.value = dep.dept_id;
                opt.textContent = dep.dept_name;
                departmentFilter.appendChild(opt);
            });
        } else {
            showToast("Failed to load departments", "error");
        }
    } catch (err) {
        console.error(err);
        showToast("Error loading departments", "error");
    }
}

async function loadCategories() {
    try {
        const res = await fetch('/inventory_manager/api/category/get_all.php');
        const data = await res.json();
        if (data.success) {
            data.category.forEach(cat => {
                const opt = document.createElement('option');
                opt.value = cat.cat_id;
                opt.textContent = cat.cat_name ;
                categoryFilter.appendChild(opt);
            });
        } else {
            showToast("Failed to load departments", "error");
        }
    } catch (err) {
        console.error(err);
        showToast("Error loading departments", "error");
    }
}

async function buildCategoryOptions(selectedId = null) {
  try {
    const res = await fetch('/inventory_manager/api/category/get_all.php');
    const data = await res.json();
    if (!data.success || !data.category) {
      return '<option value="">No categories found</option>';
    }
    return data.category.map(cat => `
      <option value="${cat.cat_id}" ${cat.cat_id == selectedId ? 'selected' : ''}>
        ${cat.cat_name}
      </option>
    `).join('');
  } catch (err) {
    console.error('Error loading categories:', err);
    return '<option value="">Error loading categories</option>';
  }
}

window.buildCategoryOptions = buildCategoryOptions;

async function buildDepartmentOptions() {
  try {
    const res = await fetch('/inventory_manager/api/department/get_active.php');
    const data = await res.json();
    if (!data.success || !data.departments) {
      return '<option value="">No department found</option>';
    }
    return data.departments.map(department => `
      <option value="${department.dept_id}">
        ${department.dept_name}
      </option>
    `).join('');
  } catch (err) {
    console.error('Error loading departments:', err);
    return '<option value="">Error loading departments</option>';
  }
}

window.buildDepartmentOptions = buildDepartmentOptions;

async function buildEmployeeOptions(deptId){
    try{
        const res = await fetch(`/inventory_manager/api/employees/get_by_dept.php?dept_id=${deptId}`);
        const data = await res.json();
         if (!data.success || !data.employees) {
            return '<option value="">No employees found</option>';
    }
    return data.employees.map(employee => `
      <option value="${employee.emp_id}">
        ${employee.emp_name}
      </option>
    `).join('');
    }catch(err){
        console.error('Error loading employees:', err);
        return '<option value="">Error loading employees</option>';
    }
}
window.buildEmployeeOptions = buildEmployeeOptions;

function buildStatusOptions(){
  return ` <select class="status-select" id="statusOption" name="statusOption">
                        <option value = "">All Status</option>
                        <option value="out of Service">Out of Service</option>
                        <option value="standby">Standby</option>
                        <option value="active">Active</option>
                        <option value="in Maintenance">In Maintenance</option>
                    </select>`
}
window.buildStatusOptions = buildStatusOptions;

// ==== Render Item ====
function displayItem(items) {
    tbody.innerHTML = '';
   
    items.forEach(item => {
        const tr = document.createElement('tr');

        const tdId = document.createElement('td'); 
        tdId.textContent = item.item_id;

        const tdCode = document.createElement('td');
        tdCode.textContent = item.item_code;

        const tdCat = document.createElement('td'); 
        tdCat.textContent = item.cat_name;

        const tdType = document.createElement('td');
        tdType.textContent = item.item_type;

        const tdStatus = document.createElement('td');
        tdStatus.textContent = item.item_status;

        const tdAssi = document.createElement('td');
        if (item.emp_name) {
            tdAssi.textContent = item.emp_name;
        } else if(item.item_dept_name) {
            tdAssi.textContent = item.item_dept_name;
        } else {
            tdAssi.textContent = "Not assigned";
        }

        const tdWarn = document.createElement('td');
        const ws = item.warranty_status ?? null;

        if (ws) {
            tdWarn.textContent = ws;
            if (ws.toLowerCase().includes('expired')) tdWarn.style.color = "red";
            else if (ws.toLowerCase().includes('under')) tdWarn.style.color = "green";
            else tdWarn.style.color = "gray";
        } else if (item.warranty_end) {
            const warrantyEnd = new Date(item.warranty_end);
            const today = new Date();
            warrantyEnd.setHours(0, 0, 0, 0);
            today.setHours(0, 0, 0, 0);

            if (today > warrantyEnd) {
                tdWarn.textContent = "Warranty expired";
                tdWarn.style.color = "red";
            } else {
                tdWarn.textContent = "Under warranty";
                tdWarn.style.color = "green";
            }
        } else {
            tdWarn.textContent = "No warranty info";
            tdWarn.style.color = "gray";
        }

        const tdActions = document.createElement('td');

        if (window.userType === 'admin') {
            const dropdown = document.createElement('div');
            dropdown.classList.add('dropdown');

            const btn = document.createElement('button');
            btn.classList.add('dropdown-btn'); 
            btn.textContent = '⋮';

            const menu = document.createElement('ul');
            menu.classList.add('dropdown-menu');

            // Edit - يظهر دائماً
            const liEdit = document.createElement('li');
            const editLink = document.createElement('a');
            editLink.href = '#'; 
            editLink.classList.add('edit');
            editLink.setAttribute('data-action', 'edit');          
            editLink.innerHTML = '<span class="material-symbols-outlined">edit</span>Edit';
            liEdit.appendChild(editLink);
            menu.appendChild(liEdit);

            // التحقق من نوع العنصر
            const isSoftware = item.item_type && item.item_type.toLowerCase() === 'software';

            // إذا لم يكن Software، أضف باقي الخيارات
            if (!isSoftware) {
                // Transfer
                const liTransfer = document.createElement('li');
                const transferLink = document.createElement('a');
                transferLink.href = '#';
                transferLink.classList.add('transfer');
                transferLink.setAttribute('data-action', 'transfer');
                transferLink.innerHTML = '<span class="material-symbols-outlined">arrows_left_right_circle</span>Transfer';
                liTransfer.appendChild(transferLink);
                menu.appendChild(liTransfer);

                // Return
                const liReturn = document.createElement('li');
                const returnLink = document.createElement('a');
                returnLink.href = '#';
                returnLink.classList.add('return');
                returnLink.setAttribute('data-action', 'return');
                returnLink.innerHTML = '<span class="material-symbols-outlined">redo</span>Return';
                liReturn.appendChild(returnLink);
                menu.appendChild(liReturn);

                // Assign
                const liAssign = document.createElement('li');
                const assignLink = document.createElement('a');
                assignLink.href = '#';
                assignLink.classList.add('assign');
                assignLink.setAttribute('data-action', 'assign');
                assignLink.innerHTML = '<span class="material-symbols-outlined">face_up</span>Assign';
                liAssign.appendChild(assignLink);
                menu.appendChild(liAssign);

                // Change Status
                const liChange = document.createElement('li');
                const ChangeLink = document.createElement('a');
                ChangeLink.href = '#';
                ChangeLink.classList.add('change');
                ChangeLink.setAttribute('data-action', 'change');
                ChangeLink.innerHTML = '<span class="material-symbols-outlined">display_settings</span>Change Status';
                liChange.appendChild(ChangeLink);
                menu.appendChild(liChange);
            }

            dropdown.append(btn, menu);
            tdActions.appendChild(dropdown);
        }

        tr.append(tdId, tdCode, tdCat, tdType, tdStatus, tdAssi, tdWarn, tdActions);
        tbody.appendChild(tr);
    });
}
// ==== View Item Details ====
document.querySelector('#itemTable tbody').addEventListener('click', async function (e) {
    // استثني الضغط على الأزرار والقائمة المنسدلة
    if (e.target.closest('.dropdown') ||
        e.target.closest('.dropdown-btn') ||
        e.target.closest('.dropdown-menu')) {
        return;
    }
    // تأكد أن النقرة على خلية
    const td = e.target.closest('td');
    if (!td) return;
    const tr = e.target.closest('tr');
    if (!tr) return;

    // استثني عمود الإجراءات (العمود الأخير)
    const cells = Array.from(tr.querySelectorAll('td'));
    const isActionsColumn = td === cells[cells.length - 1];
    if (isActionsColumn) return;

    const itemId = tr.querySelector('td:first-child').textContent.trim();
    if (!itemId) return;

    try {
        const res = await fetch(`/inventory_manager/api/item/get_single.php?id=${itemId}`);
        const data = await res.json();
        if (data.success && data.item) {
            showItemModal(data.item);
        } else {
            showToast("Failed to load item details", "error");
        }
    } catch (err) {
        console.error(err);
        showToast("Error loading item details", "error");
    }
});

// ==== Fetch Item ====
//A global variable in which all elements returned by the fetchItem() function are stored.
let allItems = [];

async function fetchItem() {
    const q = searchInput.value.trim();
    const dept = departmentFilter.value;
    const cat = categoryFilter.value;
    const status = statusFilter.value;
    const type = typeFilter.value;

    const params = new URLSearchParams({
        q,
        dept,
        cat,
        status,
        type,
        page: currentPage,
        limit
    });

    try {
        const res = await fetch(`/inventory_manager/api/item/get.php?${params.toString()}`);
        const data = await res.json();
        if (data.success) {
            allItems = data.item; // خزّن كل العناصر
            displayItem(data.item);
            renderPagination(data.total, data.page, data.limit);
            return allItems;
        }
    } catch (err) {
        console.error(err);
        showToast("Error fetching employees", "error");
    }
}

window.fetchItem = fetchItem;


// ==== Search ====
searchInput.addEventListener('input', debounce(()=>{ currentPage=1; fetchItem(); }, 300));

resetFiltersBtn.addEventListener('click', () => {
    searchInput.value = '';
    departmentFilter.value = '';
    categoryFilter.value = '';
    statusFilter.value = '';
    typeFilter.value = '';
    currentPage = 1;
    fetchItem(); // إعادة تحميل كل البيانات بدون فلاتر
});

// ==== Dropdown ====
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
});

function showItemModal(item) {
    const modal = document.getElementById('readModal');
    const content = modal.querySelector('.modal-content');
    
    // حساب حالة الضمان للعرض
    let warrantyDisplay = 'No warranty info';
    let warrantyColor = 'gray';
    
    if (item.warranty_status) {
        warrantyDisplay = item.warranty_status;
        if (item.warranty_status.toLowerCase().includes('expired')) {
            warrantyColor = 'red';
        } else if (item.warranty_status.toLowerCase().includes('under')) {
            warrantyColor = 'green';
        }
    } else if (item.warranty_end) {
        const warrantyEnd = new Date(item.warranty_end);
        const today = new Date();
        warrantyEnd.setHours(0, 0, 0, 0);
        today.setHours(0, 0, 0, 0);
        
        if (today > warrantyEnd) {
            warrantyDisplay = 'Warranty expired';
            warrantyColor = 'red';
        } else {
            warrantyDisplay = 'Under warranty';
            warrantyColor = 'green';
        }
    }
    
    content.innerHTML = `
        <span class="close-btn">&times;</span>
        <h3 class="form-header">Item Details</h3>
        <hr style="border: 1px solid rgb(241, 234, 234);">
        <div class="item-details">
            <p><strong>ID:</strong> ${item.item_id}</p>
            <p><strong>Code:</strong> ${item.item_code}</p>
            <p><strong>Category:</strong> ${item.cat_name || '-'}</p>
            <p><strong>Type:</strong> ${item.item_type}</p>
            <p><strong>Status:</strong> ${item.item_status}</p>
            <p><strong>Assigned to:</strong> ${item.emp_name || item.item_dept_name || 'Not assigned'}</p>
            <p><strong>Purchase Date:</strong> ${item.purchase_date || '-'}</p>
            <p><strong>Warranty (Months):</strong> ${item.warranty_months || '-'}</p>
            <p><strong>Warranty End:</strong> ${item.warranty_end || '-'}</p>
            <p><strong>Warranty Status:</strong> <span style="color: ${warrantyColor}; font-weight: bold;">${warrantyDisplay}</span></p>
            <p><strong>Description:</strong> ${item.description || '-'}</p>
        </div>
    `;

    modal.style.display = 'block';

    const closeBtn = content.querySelector('.close-btn');
    closeBtn.onclick = () => modal.style.display = 'none';

    window.onclick = (event) => {
        if (event.target === modal) modal.style.display = 'none';
    };
}

// تعريف mapping بين الـ action ونوع المودل
const modalTypes = {
    edit: 'EditModal',
    transfer: 'TransferModal',
    return: 'ReturnModal',
    assign: 'AssignModal',
    change: 'changeStatus',
};

// Event Delegation
tbody.addEventListener('click', function(e) {
    const link = e.target.closest('.dropdown-menu a');
    if (!link) return;
    
    e.preventDefault();
    
    const tr = e.target.closest('tr');
    if (!tr) return;
    
    const itemId = tr.querySelector('td:first-child').textContent.trim();
    const action = link.dataset.action;
    const modalType = modalTypes[action];
    const itemData = allItems.find(i => i.item_id == itemId);
    
    // فحص خاص بـ Assign: إذا كان العنصر معين بالكامل، نمنع فتح المودال
    if (action === 'assign') {
        // التحقق من وجود dept_id أو item_dept_name
        const hasDept = itemData.dept_id || itemData.item_dept_name;
        const hasEmp = itemData.emp_id || itemData.emp_name;
        
        if (hasDept && hasEmp) {
            showToast('Item is already fully assigned. Use Transfer to change assignment.', 'error');
            return;
        }
    }
    
    // فحص خاص بـ Transfer: إذا كان العنصر غير معين لأي قسم، نمنع فتح المودال
    if (action === 'transfer') {
        // التحقق من وجود dept_id أو item_dept_name
        const hasDept = itemData.dept_id || itemData.item_dept_name;
        
        if (!hasDept) {
            showToast('Cannot transfer unassigned item. Please use Assign first.', 'error');
            return;
        }
    }
    
    openModal(modalType, itemData);
});

 function openModal(modalType, itemData) {
  switch (modalType) {
    case 'EditModal':
      buildEditModal(itemData);
      break;
    case 'TransferModal':
      buildTransferModal(itemData);
      break;
    case 'ReturnModal':
      buildReturnModal(itemData);
      break;
    case 'AssignModal':
      buildAssignModal(itemData);
      break;
    case 'changeStatus':
      buildChangeStatusModal(itemData);
      break;
  }
}


document.addEventListener('DOMContentLoaded', () => {
    loadDepartments();
    loadCategories();
    fetchItem();
});