const reportFormApp = (function () {
    // floor counts include the ground floor
    const locationFloors = { 'JMB': 4, 'ANNEX I': 12, 'MB': 8 };

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

    const REPORT_DATE_MIN = '2022-01-01';

    function applyReportDateLimits(dateInput) {
        if (!dateInput) return;
        const today = formatDateInputValue(new Date());
        dateInput.max = dateInput.max || today;
        dateInput.min = dateInput.min || REPORT_DATE_MIN;
    }

    function buildLocationValue(locationSelect, floorSelect) {
        const location = locationSelect.value;
        if (!location) return '';
        if (!floorSelect.classList.contains('floor-select-hidden')) {
            if (!floorSelect.value) return '';
            return `${location} - ${floorSelect.value}`;
        }
        return location;
    }

    function resetForm(form, buttons, preview, removeBtn, uploadText, uploadBox, dateInput) {
        form.reset();
        buttons.forEach(button => button.classList.remove('active'));
        if (preview) preview.classList.add('hidden');
        if (removeBtn) removeBtn.classList.add('hidden');
        if (uploadText) uploadText.textContent = 'Click to upload image';
        if (uploadBox) uploadBox.classList.remove('has-image');
        if (typeof ReportDatePicker !== 'undefined' && dateInput) {
            ReportDatePicker.reset(dateInput);
        }
    }

    function ordinalSuffix(n) {
        const j = n % 10,
              k = n % 100;
        if (j == 1 && k != 11) return 'st';
        if (j == 2 && k != 12) return 'nd';
        if (j == 3 && k != 13) return 'rd';
        return 'th';
    }

    function createFloors(locationSelect, floorSelect) {
        const selected = locationSelect.value;
        const count = locationFloors[selected];
        if (count) {
            floorSelect.innerHTML = '<option value="">Select floor</option>';
            for (let i = 0; i < count; i++) {
                const option = document.createElement('option');
                if (i === 0) {
                    option.value = 'Ground Floor';
                    option.textContent = 'Ground Floor';
                } else {
                    // Label floors as 2nd, 3rd, ... after Ground Floor
                    const labelNum = i + 1; // i=1 -> 2, i=2 -> 3, etc.
                    option.value = `${labelNum}${ordinalSuffix(labelNum)} Floor`;
                    option.textContent = option.value;
                }
                floorSelect.appendChild(option);
            }
            floorSelect.classList.remove('floor-select-hidden');
        } else {
            floorSelect.innerHTML = '';
            floorSelect.value = '';
            floorSelect.classList.add('floor-select-hidden');
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
            if (typeof ReportDatePicker !== 'undefined') {
                ReportDatePicker.initAll(form);
            }
            if (locationSelect && floorSelect) {
                locationSelect.addEventListener('change', () => createFloors(locationSelect, floorSelect));
                createFloors(locationSelect, floorSelect);
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
                    showPopup('error', 'Please select a date from January 2022 onward.');
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
                                resetForm(form, buttons, preview, removeBtn, uploadText, uploadBox, dateInput);
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
                                    if (popupOk) popupOk.onclick = () => { hidePopup(); resetForm(form, buttons, preview, removeBtn, uploadText, uploadBox, dateInput); };
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
