export async function buildChangeStatusModal(itemData) {
  // حذف أي مودال مفتوح
  let existing = document.querySelector('#chanegModal');
  if (existing) existing.remove();

  const modal = document.createElement('div');
  modal.id = 'changeModal';
  modal.classList.add('modal-overlay');
  modal.style.display = 'flex';

  const modalContent = document.createElement('div');
  modalContent.classList.add('modal-content');

  const header = document.createElement('div');
  header.classList.add('modal-header');
  header.innerHTML = `<h3>Change Status</h3><span class="close-btn">&times;</span>`;

  const form = document.createElement('form');
  form.classList.add('change-form');
  form.id = 'chanegItemForm';

  // جلب خيارات الأقسام
  const statustOptions =  window.buildStatusOptions();

  // : رسالة توضيحية إذا كان القسم محدد مسبقاً
  const infoMessage = !itemData.item_dept_name && itemData.item_status == "Standby" ? 
    `<p class="info-msg" style="background: #e3f2fd; padding: 10px; border-radius: 5px; color: #1976d2; margin-bottom: 15px;">
     This item is currently unassigned, so its default status has been set to Standby. 
     Are you sure you want to change the status?
    </p>` : '';

  // بناء الفورم
  form.innerHTML = `
    ${infoMessage}
    <label>Item Code</label>
    <input type="text" name="item_code" value="${itemData.item_code}" readonly>

    <label>Status</label>
      ${statustOptions}
    
    
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


  // معالجة الفورم عند الحفظ
  form.addEventListener('submit', async e => {
    e.preventDefault();
    form.querySelectorAll('.error-msg')?.forEach(el => el.remove());

    saveBtn.disabled = true;
    saveBtn.textContent = 'Saving...';

    const formData = new FormData(form);
    
    const data = {
      item_id: itemData.item_id,
      item_status: formData.get('statusOption'),
    };



    try {
      const res = await fetch('/inventory_manager/api/item/changeStatus.php', {
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
          errEl.style.display = 'block'; // ✅ إضافة هذا
          errEl.style.marginTop = '5px'; // ✅ إضافة مسافة
          errEl.textContent = msg;
          input.insertAdjacentElement('afterend', errEl);
        }
      }
      window.showToast?.('Please fix the errors', 'error');
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