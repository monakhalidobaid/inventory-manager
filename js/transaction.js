const tbody = document.querySelector('#itemTable tbody');
const searchInput = document.getElementById('searchInput');
const transactionFilter = document.getElementById('tranTypeFilter');
const resetFiltersBtn = document.getElementById('resetFiltersBtn');

let currentPage = 1;
let limit = 5;
let allItems = [];

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
      fetchTransaction();
    });

    pagination.appendChild(btn);
  }
}

// ==== Render Transaction ====
function displayTransaction(items) {
    tbody.innerHTML = '';
   
    items.forEach(item => {
        const tr = document.createElement('tr');

        const tdId = document.createElement('td'); 
        tdId.textContent = item.trans_id;

        const tdItemId = document.createElement('td');
        tdItemId.textContent = item.item_id;

        const tdCode = document.createElement('td'); // ✅ عمود جديد
        tdCode.textContent = item.item_code || '-';
        const tdType = document.createElement('td'); 
        tdType.textContent = item.trans_type;

        const tdDate = document.createElement('td');
        tdDate.textContent = item.trans_date;

        const tdUser = document.createElement('td');
        tdUser.textContent = item.created_by_name;


        tr.append(tdId, tdItemId, tdCode, tdType, tdDate, tdUser); // ✅ إضافة tdCode
        tbody.appendChild(tr);
    });
}

// ==== Fetch Transaction ====
async function fetchTransaction() {
    const q = searchInput.value.trim();
    const transaction = transactionFilter.value;

    const params = new URLSearchParams({
        q,
        transaction,
        page: currentPage,
        limit
    });

    try {
        const res = await fetch(`/inventory_manager/api/transaction/get_transaction.php?${params.toString()}`);
        const data = await res.json();
        if (data.success) {
            allItems = data.item;
            displayTransaction(data.item); // ✅ تم التصحيح
            renderPagination(data.total, data.page, data.limit);
            return allItems;
        }
    } catch (err) {
        console.error(err);
        showToast("Error fetching transaction list", "error");
    }
}

// ==== Show Transaction Modal ====
function showTransactionModal(item) {
    const modal = document.getElementById('readModal');
    const content = modal.querySelector('.modal-content');
    
    // دالة مساعدة لعرض الموظف أو القسم
    function getDisplayValue(empName, deptName) {
        if (empName && deptName) {
            // إذا كان هناك موظف وقسم، اعرضهما معاً
            return `${empName} (${deptName})`;
        } else if (deptName) {
            // إذا كان قسم فقط
            return deptName;
        } else if (empName) {
            // إذا كان موظف فقط (حالة نادرة)
            return empName;
        }
        return '-';
    }
    
    // الحقول الأساسية الموجودة في كل الأنواع
    let detailsHTML = `
        <p><strong>ID:</strong> ${item.trans_id}</p>
        <p><strong>Item ID:</strong> ${item.item_id}</p>
        <p><strong>Item Code:</strong> ${item.item_code}</p>
        <p><strong>Transaction Type:</strong> ${item.trans_type || '-'}</p>
        <p><strong>Transaction Date:</strong> ${item.trans_date}</p>
    `;
    
    // إضافة الحقول المخصصة حسب نوع العملية
    switch(item.trans_type?.toLowerCase()) {
        case 'assign':
            const toValue = getDisplayValue(item.to_emp_name, item.to_dept_name);
            detailsHTML += `
                <p><strong>Assigned To:</strong> ${toValue}</p>
                <p><strong>Old Status:</strong> ${item.old_status || '-'}</p>
                <p><strong>New Status:</strong> ${item.new_status || '-'}</p>
            `;
            break;
            
        case 'transfer':
            const fromValue = getDisplayValue(item.from_emp_name, item.from_dept_name);
            const toTransferValue = getDisplayValue(item.to_emp_name, item.to_dept_name);
            detailsHTML += `
                <p><strong>From:</strong> ${fromValue}</p>
                <p><strong>To:</strong> ${toTransferValue}</p>
            `;
            break;
            
        case 'return':
            const returnFromValue = getDisplayValue(item.from_emp_name, item.from_dept_name);
            detailsHTML += `
                <p><strong>Returned From:</strong> ${returnFromValue}</p>
                <p><strong>Old Status:</strong> ${item.old_status || '-'}</p>
                <p><strong>New Status:</strong> ${item.new_status || '-'}</p>
            `;
            break;
            
        case 'edit':
        case 'delete':
            detailsHTML += `
                <p><strong>Notes:</strong> ${item.notes || '-'}</p>
            `;
            break;
            
        default:
            // في حالة نوع عملية غير معروف، نعرض كل الحقول
            const defaultFrom = getDisplayValue(item.from_emp_name, item.from_dept_name);
            const defaultTo = getDisplayValue(item.to_emp_name, item.to_dept_name);
            detailsHTML += `
                <p><strong>From:</strong> ${defaultFrom}</p>
                <p><strong>To:</strong> ${defaultTo}</p>
                <p><strong>Old Status:</strong> ${item.old_status || '-'}</p>
                <p><strong>New Status:</strong> ${item.new_status || '-'}</p>
                <p><strong>Notes:</strong> ${item.notes || '-'}</p>
            `;
    }
    
    // إضافة Created by في النهاية لكل الأنواع
    detailsHTML += `<p><strong>Created by:</strong> ${item.created_by_name || '-'}</p>`;
    
    content.innerHTML = `
        <span class="close-btn">&times;</span>
        <h3 class="form-header">Transaction Details</h3>
        <hr style="border: 1px solid rgb(241, 234, 234);">
        <div class="item-details">
            ${detailsHTML}
        </div>
    `;

    modal.style.display = 'block';

    const closeBtn = content.querySelector('.close-btn');
    closeBtn.onclick = () => modal.style.display = 'none';

    window.onclick = (event) => {
        if (event.target === modal) modal.style.display = 'none';
    };
}
// ==== Initialize when DOM is ready ====
document.addEventListener('DOMContentLoaded', () => {
    // Event Listeners
    transactionFilter.addEventListener('change', () => {
        currentPage = 1;
        fetchTransaction();
    });

    searchInput.addEventListener('input', debounce(() => { 
        currentPage = 1; 
        fetchTransaction(); // ✅ تم التصحيح
    }, 300));

    resetFiltersBtn.addEventListener('click', () => {
        searchInput.value = '';
        transactionFilter.value = '';
        currentPage = 1;
        fetchTransaction(); // ✅ تم التصحيح
    });

    // View Transaction Details - Click on row
    tbody.addEventListener('click', async function (e) {
        const td = e.target.closest('td');
        if (!td) return;
        const tr = e.target.closest('tr');
        if (!tr) return;

        const transId = tr.querySelector('td:first-child').textContent.trim();
        if (!transId) return;

        try {
            const res = await fetch(`/inventory_manager/api/transaction/get_single.php?id=${transId}`);
            const data = await res.json();
            if (data.success && data.item) {
                showTransactionModal(data.item);
            } else {
                 window.showToast("Failed to load transaction details", "error");
            }
        } catch (err) {
            console.error(err);
             window.showToast("Error loading transaction details", "error");
        }
    });

    // Initial fetch
    fetchTransaction();
});