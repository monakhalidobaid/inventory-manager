export async function buildReturnModal(itemData) {
  // حذف أي مودال مفتوح
  let existing = document.querySelector('#returnModal');
  if (existing) existing.remove();

  const modal = document.createElement('div');
  modal.id = 'returnModal';
  modal.classList.add('modal-overlay');
  modal.style.display = 'flex';

  const modalContent = document.createElement('div');
  modalContent.classList.add('modal-content');

  const header = document.createElement('div');
  header.classList.add('modal-header');
  header.innerHTML = `<h3>⚠️ Return Item</h3><span class="close-btn">&times;</span>`;

  const form = document.createElement('form');
  form.classList.add('return-form');
  form.id = 'returnItemForm';


  // بناء الفورم
  form.innerHTML = `
    <p> 
        Warning: Returning this item will change its status to Standby and remove the assigned department and employee. Are you sure you want to proceed?
    </p>

    
    <div class="modal-footer">
      <button type="button" class="cancel-btn">Cancel</button>
      <button type="button" class="return-btn">Return</button>
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
  const saveBtn = form.querySelector('.return-btn');
  saveBtn.onclick = () => form.dispatchEvent(new Event('submit', { bubbles: true, cancelable: true }));


  //معالجة الفورم 
  form.addEventListener('submit', async e => {
    e.preventDefault();

    
    const data = {
      item_id: itemData.item_id,  
    };

    

    try {
      const res = await fetch('/inventory_manager/api/item/return.php', {
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