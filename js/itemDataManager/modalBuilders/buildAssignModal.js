export async function buildAssignModal(itemData) {
  // حذف أي مودال مفتوح
  let existing = document.querySelector('#assignModal');
  if (existing) existing.remove();

  const modal = document.createElement('div');
  modal.id = 'assignModal';
  modal.classList.add('modal-overlay');
  modal.style.display = 'flex';

  const modalContent = document.createElement('div');
  modalContent.classList.add('modal-content');

  const header = document.createElement('div');
  header.classList.add('modal-header');
  header.innerHTML = `<h3>Assign Item</h3><span class="close-btn">&times;</span>`;

  const form = document.createElement('form');
  form.classList.add('assign-form');
  form.id = 'assignItemForm';

  // جلب خيارات الأقسام
  const departmentOptions = await window.buildDepartmentOptions();

  // : رسالة توضيحية إذا كان القسم محدد مسبقاً
  const infoMessage = itemData.dept_id ? 
    `<p class="info-msg" style="background: #e3f2fd; padding: 10px; border-radius: 5px; color: #1976d2; margin-bottom: 15px;">
      ✓ This item is already assigned to <strong>${itemData.item_dept_name}</strong>. You can now select an employee.
    </p>` : '';

  // بناء الفورم
  form.innerHTML = `
    ${infoMessage}
    <label>Item Code</label>
    <input type="text" name="item_code" value="${itemData.item_code}" readonly>
    
    <label>Department</label>
    <select name="dept_id" id="deptSelect" required>
      <option value="">Select department</option>
      ${departmentOptions}
    </select>
    
    <label>Employee (Optional)</label>
    <select name="emp_id" id="empSelect">
      <option value="">Select employee (or leave empty)</option>
    </select>
    
    <div class="modal-footer">
      <button type="button" class="cancel-btn">Cancel</button>
      <button type="button" class="save-btn">Save</button>
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

  // فكرة 3: تحديد القسم المحفوظ مسبقاً وتحميل الموظفين
  if (itemData.dept_id) {
    deptSelect.value = itemData.dept_id;
    // تحميل الموظفين للقسم المحدد
    empSelect.innerHTML = '<option value="">Loading...</option>';
    const employeeOptions = await window.buildEmployeeOptions(itemData.dept_id);
    empSelect.innerHTML = '<option value="">Select employee (or leave empty)</option>' + employeeOptions;
  }

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
    saveBtn.textContent = 'Saving...';

    const formData = new FormData(form);
    
    // فكرة 2: السماح بحفظ بدون موظف
    const data = {
      item_id: itemData.item_id,
      item_type: itemData.item_type,
      dept_id: formData.get('dept_id'),
      emp_id: formData.get('emp_id') || null // null إذا فارغ
    };

    // للتأكد من البيانات

    try {
      const res = await fetch('/inventory_manager/api/item/assign.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
      });

      // طباعة الاستجابة للتحقق من المشكلة
      const responseText = await res.text();
      
      const result = JSON.parse(responseText);

      if (result.success) {
        window.showToast?.(result.message || 'Item updated successfully', 'success');
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
        saveBtn.textContent = 'Save';
      } else {
        window.showToast?.(result.message || 'Update failed', 'error');
        saveBtn.disabled = false;
        saveBtn.textContent = 'Save';
      }
    } catch (err) {
      console.error(err);
      window.showToast?.('Error updating item: ' + err.message, 'error');
      saveBtn.disabled = false;
      saveBtn.textContent = 'Save';
    }
  });
}