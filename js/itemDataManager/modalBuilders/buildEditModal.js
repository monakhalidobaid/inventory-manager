export async function buildEditModal(itemData) {
  // حذف أي مودال مفتوح
  let existing = document.querySelector('#editModal');
  if (existing) existing.remove();

  const modal = document.createElement('div');
  modal.id = 'editModal';
  modal.classList.add('modal-overlay');
  modal.style.display = 'flex';

  const modalContent = document.createElement('div');
  modalContent.classList.add('modal-content');

  const header = document.createElement('div');
  header.classList.add('modal-header');
  header.innerHTML = `<h3>Edit Item</h3><span class="close-btn">&times;</span>`;

  const form = document.createElement('form');
  form.classList.add('edit-form');
  form.id = 'editItemForm';

  if (itemData.item_type === 'software') {
    form.innerHTML = `
      <label>Item Code</label>
      <input type="text" name="item_code" value="${itemData.item_code}" readonly>

      <label>Description</label>
      <textarea name="description" rows="4">${itemData.description || ''}</textarea>

      <div class="modal-footer">
        <button type="button" class="cancel-btn">Cancel</button>
        <button type="button" class="save-btn">Save</button>
      </div>
    `;
  } else if (itemData.item_type === 'hardware') {
    const categoryOptions = await window.buildCategoryOptions(itemData.cat_id);
    form.innerHTML = `
      <label>Item Code</label>
      <input type="text" name="item_code" value="${itemData.item_code}" readonly>

      <label>Category</label>
      <select name="cat_id" required>${categoryOptions}</select>

      <label>Purchase Date</label>
      <input type="date" name="purchase_date" value="${itemData.purchase_date || ''}">

      <label>Warranty (Months)</label>
      <input type="number" name="warranty_months" value="${itemData.warranty_months || ''}" min="0">

      <label>Warranty End</label>
      <input type="date" name="warranty_end" value="${itemData.warranty_end || ''}" readonly>

      <label>Description</label>
      <textarea name="description" rows="4">${itemData.description || ''}</textarea>

      <div class="modal-footer">
        <button type="button" class="cancel-btn">Cancel</button>
        <button type="button" class="save-btn">Save</button>
      </div>
    `;

    // حساب تلقائي لتاريخ انتهاء الضمان
    const purchaseDateInput = form.querySelector('[name="purchase_date"]');
    const warrantyMonthsInput = form.querySelector('[name="warranty_months"]');
    const warrantyEndInput = form.querySelector('[name="warranty_end"]');
    function calculateWarrantyEnd() {
      const purchaseDate = purchaseDateInput.value;
      const months = parseInt(warrantyMonthsInput.value);
      if (purchaseDate && months > 0) {
        const date = new Date(purchaseDate);
        date.setMonth(date.getMonth() + months);
        warrantyEndInput.value = date.toISOString().split('T')[0];
      } else warrantyEndInput.value = '';
    }
    purchaseDateInput.addEventListener('change', calculateWarrantyEnd);
    warrantyMonthsInput.addEventListener('input', calculateWarrantyEnd);
  }

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

  // زر الحفظ الآن يرسل الفورم يدويًا
  const saveBtn = form.querySelector('.save-btn');
  saveBtn.onclick = () => form.dispatchEvent(new Event('submit', { bubbles: true, cancelable: true }));

  // معالجة الفورم
  // معالجة الفورم
  form.addEventListener('submit', async e => {
    e.preventDefault();

    // إزالة أي رسائل خطأ سابقة
    form.querySelectorAll('.error-msg')?.forEach(el => el.remove());

    saveBtn.disabled = true;
    saveBtn.textContent = 'Saving...';

    const formData = new FormData(form);
    const data = { item_id: itemData.item_id, item_type: itemData.item_type };

    if (itemData.item_type === 'software') {
      data.description = formData.get('description').trim().toLowerCase();
    } else if (itemData.item_type === 'hardware') {
      data.cat_id = formData.get('cat_id');
      data.purchase_date = formData.get('purchase_date');
      data.warranty_months = formData.get('warranty_months');
      data.warranty_end = formData.get('warranty_end');
      data.description = formData.get('description').trim().toLowerCase();
    }

    try {
      const res = await fetch('/inventory_manager/api/item/update.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
      });
      const result = await res.json();

      if (result.success) {
        window.showToast?.(result.message || 'Item updated successfully', 'success');
        closeModal();
        window.fetchItem?.();
      } 
      else if (result.errors) {
        // عرض رسائل الخطأ أسفل كل حقل
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
      } 
      else {
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
