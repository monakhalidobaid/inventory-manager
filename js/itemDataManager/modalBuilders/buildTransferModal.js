export async function buildTransferModal(itemData) {
  // حذف أي مودال مفتوح
  let existing = document.querySelector('#transferModal');
  if (existing) existing.remove();

  const modal = document.createElement('div');
  modal.id = 'transferModal';
  modal.classList.add('modal-overlay');
  modal.style.display = 'flex';

  const modalContent = document.createElement('div');
  modalContent.classList.add('modal-content');

  const header = document.createElement('div');
  header.classList.add('modal-header');
  header.innerHTML = `<h3>Transfer Item</h3><span class="close-btn">&times;</span>`;

  const form = document.createElement('form');
  form.classList.add('transfer-form');
  form.id = 'transferItemForm';

  // جلب خيارات الأقسام
  const departmentOptions = await window.buildDepartmentOptions();

  // طباعة البيانات للتحقق
  console.log('Transfer Modal - Item Data:', itemData);

  // بناء الفورم
  form.innerHTML = `
    <label>Item Code</label>
    <input type="text" name="item_code" value="${itemData.item_code || ''}" readonly>
    
    <label>Current Department</label>
    <input type="text" value="${itemData.item_dept_name || itemData.emp_dept_name || 'Not Assigned'}" readonly>
    
    <label>Current Employee</label>
    <input type="text" value="${itemData.emp_name || 'Not Assigned'}" readonly>
    
    <hr style="border: 1px solid #e0e0e0; margin: 20px 0;">
    
    <h4 style="color: #1976d2; margin-bottom: 15px;">Transfer To:</h4>
    
    <label>New Department </label>
    <select name="dept_id" id="deptSelect" required>
      <option value="">Select department</option>
      ${departmentOptions}
    </select>
    
    <label>New Employee (Optional)</label>
    <select name="emp_id" id="empSelect">
      <option value="">Select employee (or leave empty)</option>
    </select>
    
    <div class="modal-footer">
      <button type="button" class="cancel-btn">Cancel</button>
      <button type="button" class="save-btn">Transfer</button>
    </div>
  `;

  modalContent.append(header, form);
  modal.appendChild(modalContent);
  document.body.appendChild(modal);

  setTimeout(() => modal.classList.add('show'), 10);

  const closeModal = () => {
    modal.classList.remove('show');
    setTimeout(() => modal.remove(), 200);
  };

  // إغلاق المودال
  modal.querySelector('.close-btn').onclick = closeModal;
  modal.querySelector('.cancel-btn').onclick = closeModal;
  modal.addEventListener('click', e => { if (e.target === modal) closeModal(); });

  // زر الحفظ
  const saveBtn = form.querySelector('.save-btn');
  saveBtn.onclick = () => form.dispatchEvent(new Event('submit', { bubbles: true, cancelable: true }));

  // ربط القسم بالموظفين
  const deptSelect = form.querySelector('#deptSelect');
  const empSelect = form.querySelector('#empSelect');

  deptSelect.addEventListener('change', async () => {
    const deptId = deptSelect.value;
    if (!deptId) {
      empSelect.innerHTML = '<option value="">Select employee (or leave empty)</option>';
      return;
    }
    empSelect.innerHTML = '<option value="">Loading...</option>';
    const employeeOptions = await window.buildEmployeeOptions(deptId);
    empSelect.innerHTML = '<option value="">Select employee (or leave empty)</option>' + employeeOptions;
  });

  // معالجة الفورم عند الحفظ
  form.addEventListener('submit', async e => {
    e.preventDefault();
    form.querySelectorAll('.error-msg')?.forEach(el => el.remove());

    saveBtn.disabled = true;
    saveBtn.textContent = 'Transferring...';

    const formData = new FormData(form);
    
    const data = {
      item_id: itemData.item_id,
      item_type: itemData.item_type,
      dept_id: formData.get('dept_id'),
      emp_id: formData.get('emp_id') || null // null إذا فارغ
    };


    try {
      const res = await fetch('/inventory_manager/api/item/transfer.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
      });

      const responseText = await res.text();
      
      const result = JSON.parse(responseText);

      if (result.success) {
        window.showToast?.(result.message || 'Item transferred successfully', 'success');
        closeModal();
        window.fetchItem?.();
      } else if (result.errors) {
        for (const [field, msg] of Object.entries(result.errors)) {
          let input = form.querySelector(`[name="${field}"]`);
          if (input) {
            const errEl = document.createElement('span');
            errEl.classList.add('error-msg');
            errEl.style.color = 'red';
            errEl.style.fontSize = '0.85em';
            errEl.textContent = msg;
            input.insertAdjacentElement('afterend', errEl);
          }
        }
        saveBtn.disabled = false;
        saveBtn.textContent = 'Transfer';
      } else {
        window.showToast?.(result.message || 'Transfer failed', 'error');
        saveBtn.disabled = false;
        saveBtn.textContent = 'Transfer';
      }
    } catch (err) {
      console.error(err);
      window.showToast?.('Error transferring item: ' + err.message, 'error');
      saveBtn.disabled = false;
      saveBtn.textContent = 'Transfer';
    }
  });
}