const reportFormApp = (function () {
    const locationFloors = { 'JMB': 4, 'ANNEX I': 13, 'MB': 9 };

    function query(id) {
        return document.getElementById(id);
    }

    function showPopup(type, message, showCancel = false) {
        const overlay = query('popup-overlay');
        const title   = query('popup-title');
        const content = query('popup-content');

        if (!overlay || !title || !content || !message) return;

        title.textContent = type === 'success' ? 'Success' : type === 'warning' ? 'Warning' : 'Error';
        content.classList.remove('success', 'warning', 'error');
        content.classList.add(type);
        query('popup-message').textContent = message;
        query('popup-cancel').classList.toggle('hidden', !showCancel);
        overlay.classList.remove('hidden');
    }

    function hidePopup() {
        const overlay = query('popup-overlay');
        if (overlay) overlay.classList.add('hidden');
    }

    function formatDateInputValue(date) {
        return date.toISOString().split('T')[0];
    }

    function applyReportDateLimits(dateInput) {
        if (!dateInput) return;
        const today = new Date();
        const min = new Date();
        min.setFullYear(today.getFullYear() - 1);
        dateInput.max = formatDateInputValue(today);
        dateInput.min = formatDateInputValue(min);
    }

    function buildLocationValue(locationSelect, floorSelect) {
        const location = locationSelect.value;
        if (!location) return '';
        if (floorSelect.style.display !== 'none') {
            if (!floorSelect.value) return '';
            return `${location} - ${floorSelect.value}`;
        }
        return location;
    }

    function resetForm(form, buttons, preview, removeBtn, uploadText, uploadBox) {
        form.reset();
        buttons.forEach(button => button.classList.remove('active'));
        if (preview) preview.classList.add('hidden');
        if (removeBtn) removeBtn.classList.add('hidden');
        if (uploadText) uploadText.textContent = 'Click to upload image';
        if (uploadBox) uploadBox.classList.remove('has-image');
    }

    function createFloors(locationSelect, floorSelect) {
        const selected = locationSelect.value;
        if (locationFloors[selected]) {
            floorSelect.innerHTML = '<option value="">Select floor</option>';
            for (let i = 0; i < locationFloors[selected]; i++) {
                const option = document.createElement('option');
                option.value = i === 0 ? 'Ground Floor' : `${i === 1 ? '2nd' : i === 2 ? '3rd' : i + 'th'} Floor`;
                option.textContent = option.value;
                floorSelect.appendChild(option);
            }
            floorSelect.style.display = '';
        } else {
            floorSelect.innerHTML = '';
            floorSelect.style.display = 'none';
        }
    }

    function bindUpload(input, preview, uploadText, uploadBox, removeBtn) {
        if (!input) return;

        input.addEventListener('change', function () {
            if (!this.files || !this.files[0]) return;
            const reader = new FileReader();
            reader.onload = event => {
                if (preview) {
                    preview.src = event.target.result;
                    preview.classList.remove('hidden');
                }
                if (removeBtn) removeBtn.classList.remove('hidden');
                if (uploadText) uploadText.textContent = this.files[0].name;
                if (uploadBox) uploadBox.classList.add('has-image');
            };
            reader.readAsDataURL(this.files[0]);
        });

        if (removeBtn) {
            removeBtn.addEventListener('click', () => {
                input.value = '';
                if (preview) preview.classList.add('hidden');
                if (preview) preview.src = '';
                if (removeBtn) removeBtn.classList.add('hidden');
                if (uploadText) uploadText.textContent = 'Click to upload image';
                if (uploadBox) uploadBox.classList.remove('has-image');
            });
        }
    }

    async function submitForm(form, forceSubmit = false) {
        const formData = new FormData(form);
        if (forceSubmit) formData.append('force_submit', '1');
        const response = await fetch(form.action, {
            method: 'POST',
            body: formData,
            credentials: 'same-origin',
        });
        return response.json();
    }

    return {
        init: function () {
            const form           = query('report-form');
            const categoryInput  = query('Category');
            const locationSelect = query('location-select');
            const floorSelect    = query('floor-select');
            const hiddenLocation = query('Location');
            const dateInput      = form ? form.querySelector('input[type="date"]') : null;
            const preview        = query('image-preview');
            const uploadText     = query('upload-text');
            const uploadBox      = query('upload-box');
            const removeBtn      = query('remove-image');
            const buttons        = Array.from(document.querySelectorAll('.category-btn'));
            const popupOk        = query('popup-ok');
            const popupCancel    = query('popup-cancel');

            if (!form) return;
            applyReportDateLimits(dateInput);
            if (locationSelect && floorSelect) {
                locationSelect.addEventListener('change', () => createFloors(locationSelect, floorSelect));
            }
            bindUpload(query('item-image'), preview, uploadText, uploadBox, removeBtn);

            buttons.forEach(button => {
                button.addEventListener('click', () => {
                    buttons.forEach(item => item.classList.remove('active'));
                    button.classList.add('active');
                    if (categoryInput) categoryInput.value = button.dataset.category;
                });
            });

            if (popupOk) {
                popupOk.addEventListener('click', hidePopup);
            }
            if (popupCancel) {
                popupCancel.addEventListener('click', hidePopup);
            }

            form.addEventListener('submit', async function (event) {
                event.preventDefault();

                if (!categoryInput?.value) {
                    showPopup('error', 'Please select a category before submitting.');
                    return;
                }
                if (dateInput && dateInput.max && dateInput.value > dateInput.max) {
                    showPopup('error', 'Please select a date on or before today.');
                    return;
                }
                if (dateInput && dateInput.min && dateInput.value < dateInput.min) {
                    showPopup('error', 'Please select a date within the past year.');
                    return;
                }
                if (locationSelect && floorSelect && hiddenLocation) {
                    hiddenLocation.value = buildLocationValue(locationSelect, floorSelect);
                    if (!hiddenLocation.value) {
                        showPopup('error', 'Please select or specify the location before submitting.');
                        return;
                    }
                }

                try {
                    const result = await submitForm(form, false);
                    if (result.status === 'success') {
                        showPopup('success', result.message || 'Your report has been submitted successfully.');
                        if (popupOk) {
                            popupOk.onclick = () => {
                                hidePopup();
                                resetForm(form, buttons, preview, removeBtn, uploadText, uploadBox);
                            };
                        }
                        return;
                    }

                    if (result.status === 'warning') {
                        showPopup('warning', result.message, true);
                        if (popupOk) {
                            popupOk.onclick = async () => {
                                hidePopup();
                                const retryResult = await submitForm(form, true);
                                if (retryResult.status === 'success') {
                                    showPopup('success', retryResult.message || 'Your report has been submitted successfully.');
                                    if (popupOk) popupOk.onclick = () => { hidePopup(); resetForm(form, buttons, preview, removeBtn, uploadText, uploadBox); };
                                } else {
                                    showPopup('error', retryResult.message || 'Unable to submit. Please try again.');
                                }
                            };
                        }
                        return;
                    }

                    showPopup('error', result.message || 'Unable to submit the report. Please try again.');
                } catch (error) {
                    showPopup('error', 'Unable to submit the report. Please check your connection and try again.');
                }
            });
        }
    };
})();

document.addEventListener('DOMContentLoaded', reportFormApp.init);
