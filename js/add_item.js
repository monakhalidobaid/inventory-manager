document.addEventListener('DOMContentLoaded', function() {

    const itemTypeSelect = document.getElementById('itemType');
    const itemCodeGroup = document.getElementById('itemCodeGroup');
    const softwareFields = document.querySelectorAll('.software-field');
    const hardwareFields = document.querySelectorAll('.hardware-field');
    const purchaseDateInput = document.getElementById('purchaseDate');
    const warrantyPeriodInput = document.getElementById('warrantyPeriod');
    const warrantyExpiryInput = document.getElementById('warrantyExpiry');
    const resetBtn = document.querySelector('.btn-cancel');
    const form = document.getElementById('itemForm');

    // Form fields
    const itemCodeInput = document.getElementById('itemCode');
    const categorySelect = document.getElementById('category');
    //const deviceStatusSelect = document.getElementById('deviceStatus');
    const softwareDescInput = document.getElementById('softwareDescription');
    const hardwareDescInput = document.getElementById('hardwareDescription');

    // VALIDATION HELPERS 
    function showFieldError(field, msg) {
        const span = field.parentElement.querySelector('.error-message');
        if (span) { 
            span.textContent = msg;
            span.classList.add("show");
            span.style.display = 'block';
            span.style.color = '#e74c3c';
            span.style.fontSize = '13px';
            span.style.marginTop = '5px';
        }
        field.classList.add("input-error");
        field.style.borderColor = '#e74c3c';
    }

    function clearErrors() {
        document.querySelectorAll("#itemForm .error-message").forEach(span => {
            span.textContent = "";
            span.classList.remove("show");
            span.style.display = 'none';
        });
        document.querySelectorAll("#itemForm input, #itemForm select").forEach(input => {
            input.classList.remove("input-error");
            input.style.borderColor = '#dcdcdc';
        });
    }

    function validateItemForm() {
        const itemType = itemTypeSelect.value;
        const itemCode = itemCodeInput.value.trim();
        const categoryVal = categorySelect.value;
        
        let isValid = true;
        clearErrors();
        
        // Validate Item Type
        if (!itemType) { 
            showFieldError(itemTypeSelect, "Please select an item type"); 
            isValid = false;
        }
        
        // If no item type selected, stop validation
        if (!itemType) {
            return false;
        }
        
        // Validate Item Code (Common for both types)
        if (!itemCode) {
            showFieldError(itemCodeInput, "Item Code cannot be empty");
            isValid = false;
        } else {
            // Pattern: OCJ_XX_999 or OCJ_XXX_999
            const pattern = /^OCJ_[A-Z]{2,3}_\d{3}$/;
            if (!pattern.test(itemCode)) {
                showFieldError(itemCodeInput, "Item Code must follow format: OCJ_XX_999 or OCJ_XXX_999 (uppercase letters only)");
                isValid = false;
            }
        }
        
        // Validate Category (Required for both types)
        if (!categoryVal) {
            showFieldError(categorySelect, "Please select a category");
            isValid = false;
        }
        
        // Type-specific validation
        if (itemType === 'software') {
            const softwareDesc = softwareDescInput.value.trim();
            
            if (!softwareDesc) {
                showFieldError(softwareDescInput, "Description cannot be empty");
                isValid = false;
            }
            
        } else if (itemType === 'hardware') {
            const warrantyPeriod = warrantyPeriodInput.value.trim();
            //const deviceStatus = deviceStatusSelect.value;
            
            if (warrantyPeriod && warrantyPeriod !== '') {
                if (!/^\d+$/.test(warrantyPeriod)) {
                    showFieldError(warrantyPeriodInput, "Warranty Period must contain only numbers");
                    isValid = false;
                } else if (parseInt(warrantyPeriod) < 0) {
                    showFieldError(warrantyPeriodInput, "Warranty Period cannot be negative");
                    isValid = false;
                }
            }

        }
        
        return isValid;
    }
    // HANDLE ITEM TYPE CHANGE    
    itemTypeSelect.addEventListener('change', function() {
        const selectedType = this.value;
        
        // Hide all fields first and clear errors
        itemCodeGroup.style.display = 'none';
        document.getElementById('categoryGroup').style.display = 'none';
        softwareFields.forEach(field => field.style.display = 'none');
        hardwareFields.forEach(field => field.style.display = 'none');
        clearErrors();
        
        // Clear warranty expiry when switching types
        warrantyExpiryInput.value = '';

        if (selectedType === 'software') {
            itemCodeGroup.style.display = 'flex';
            document.getElementById('categoryGroup').style.display = 'flex';
            softwareFields.forEach(field => field.style.display = 'flex');
        } else if (selectedType === 'hardware') {
            itemCodeGroup.style.display = 'flex';
            document.getElementById('categoryGroup').style.display = 'flex';
            hardwareFields.forEach(field => field.style.display = 'flex');
        }
    });

    // CALCULATE WARRANTY EXPIRY DATE
    function calculateWarrantyExpiry() {
        const purchaseDate = purchaseDateInput.value;
        const warrantyPeriod = parseInt(warrantyPeriodInput.value);

        if (purchaseDate && warrantyPeriod && warrantyPeriod >= 0) {
            const purchase = new Date(purchaseDate);
            const expiry = new Date(purchase);
            expiry.setMonth(expiry.getMonth() + warrantyPeriod);
            
            const year = expiry.getFullYear();
            const month = String(expiry.getMonth() + 1).padStart(2, '0');
            const day = String(expiry.getDate()).padStart(2, '0');
            
            warrantyExpiryInput.value = `${year}-${month}-${day}`;
        } else {
            warrantyExpiryInput.value = '';
        }
    }

    purchaseDateInput.addEventListener('change', calculateWarrantyExpiry);
    warrantyPeriodInput.addEventListener('input', calculateWarrantyExpiry);

    // HANDLE FORM RESET
    resetBtn.addEventListener('click', function() {
        setTimeout(() => {
            itemCodeGroup.style.display = 'none';
            document.getElementById('categoryGroup').style.display = 'none';
            softwareFields.forEach(field => field.style.display = 'none');
            hardwareFields.forEach(field => field.style.display = 'none');
            warrantyExpiryInput.value = '';
            clearErrors();
        }, 0);
    });

    // FORM SUBMISSION
    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        if (!validateItemForm()) {
            return;
        }

        const itemType = itemTypeSelect.value;
        let payload = {
            item_type: itemType,
            item_code: itemCodeInput.value.trim(),
            category_id: categorySelect.value
        };

        if (itemType === 'software') {
            payload.description = softwareDescInput.value.trim();
        } else if (itemType === 'hardware') {
            payload.purchase_date = purchaseDateInput.value;
            payload.warranty_period = warrantyPeriodInput.value;
            //payload.device_status = deviceStatusSelect.value;
            payload.description = hardwareDescInput.value.trim();
        }

        try {
            const res = await fetch('../api/item/insert.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify(payload)
            });
            const data = await res.json();
            
            if (data.success) {
                showToast(data.message, 'success');
                form.reset();
                clearErrors();
                itemCodeGroup.style.display = 'none';
                document.getElementById('categoryGroup').style.display = 'none';
                softwareFields.forEach(field => field.style.display = 'none');
                hardwareFields.forEach(field => field.style.display = 'none');
                warrantyExpiryInput.value = '';
            } else {
                if (data.errors) {
                    Object.keys(data.errors).forEach(key => {
                        const fieldMap = {
                            'item_type': itemTypeSelect,
                            'item_code': itemCodeInput,
                            'category_id': categorySelect,
                            'description': itemType === 'software' ? softwareDescInput : hardwareDescInput,
                            'warranty_period': warrantyPeriodInput,
                            //'device_status': deviceStatusSelect
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
    // LOAD CATEGORIES (for hardware)    
    async function loadCategories() {
        try {
            const res = await fetch('/inventory_manager/api/category/get_all.php');
            const data = await res.json();

            if (data.success) {
                categorySelect.innerHTML = '<option value="">-- Select Category --</option>';
                data.category.forEach(cat => {
                    const opt = document.createElement('option');
                    opt.value = cat.cat_id;
                    opt.textContent = cat.cat_name;
                    categorySelect.appendChild(opt);
                });
            } else {
                showToast("Failed to load categories", "error");
            }
        } catch (err) {
            console.error(err);
            showToast("Error loading categories", "error");
        }
    }

    // Initial load
    loadCategories();
    
});