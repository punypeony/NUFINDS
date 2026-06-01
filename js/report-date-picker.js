const ReportDatePicker = (function () {
    const MONTHS = [
        'January', 'February', 'March', 'April', 'May', 'June',
        'July', 'August', 'September', 'October', 'November', 'December',
    ];
    const WEEKDAYS = ['Su', 'Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa'];

    function parseYmd(value) {
        if (!value) return null;
        const parts = value.split('-').map(Number);
        if (parts.length !== 3) return null;
        const [year, month, day] = parts;
        const date = new Date(year, month - 1, day);
        if (
            date.getFullYear() !== year ||
            date.getMonth() !== month - 1 ||
            date.getDate() !== day
        ) {
            return null;
        }
        return date;
    }

    function formatYmd(date) {
        const y = date.getFullYear();
        const m = String(date.getMonth() + 1).padStart(2, '0');
        const d = String(date.getDate()).padStart(2, '0');
        return `${y}-${m}-${d}`;
    }

    function formatDisplay(value) {
        const date = parseYmd(value);
        if (!date) return 'Select date';
        return date.toLocaleDateString('en-US', {
            month: 'long',
            day: 'numeric',
            year: 'numeric',
        });
    }

    function startOfMonth(year, month) {
        return new Date(year, month, 1);
    }

    function addMonths(year, month, delta) {
        const date = new Date(year, month + delta, 1);
        return { year: date.getFullYear(), month: date.getMonth() };
    }

    function init(input) {
        if (!input || input.dataset.pickerInit === '1') return;

        const minYmd = input.min || '2022-01-01';
        const maxYmd = input.max || formatYmd(new Date());
        const minDate = parseYmd(minYmd);
        const maxDate = parseYmd(maxYmd);
        if (!minDate || !maxDate) return;

        input.dataset.pickerInit = '1';

        const wrapper = document.createElement('div');
        wrapper.className = 'report-date-picker';
        input.parentNode.insertBefore(wrapper, input);
        wrapper.appendChild(input);
        input.classList.add('report-date-input-native');
        input.tabIndex = -1;

        const display = document.createElement('button');
        display.type = 'button';
        display.className = 'report-date-display is-placeholder';
        display.textContent = 'Select date';
        display.setAttribute('aria-haspopup', 'dialog');
        display.setAttribute('aria-expanded', 'false');
        wrapper.appendChild(display);

        const calendar = document.createElement('div');
        calendar.className = 'report-date-calendar hidden';
        calendar.setAttribute('role', 'dialog');
        calendar.setAttribute('aria-label', 'Choose date');
        calendar.innerHTML = `
            <div class="report-date-calendar-header">
                <button type="button" class="report-date-nav" data-nav="prev" aria-label="Previous month">&lsaquo;</button>
                <div class="report-date-month-year">
                    <select class="report-date-month-select" aria-label="Month"></select>
                    <select class="report-date-year-select" aria-label="Year"></select>
                </div>
                <button type="button" class="report-date-nav" data-nav="next" aria-label="Next month">&rsaquo;</button>
            </div>
            <div class="report-date-weekdays"></div>
            <div class="report-date-grid"></div>
            <div class="report-date-footer">
                <button type="button" class="report-date-today-btn">Today</button>
            </div>
        `;
        wrapper.appendChild(calendar);

        const monthSelect = calendar.querySelector('.report-date-month-select');
        const yearSelect = calendar.querySelector('.report-date-year-select');
        const weekdaysEl = calendar.querySelector('.report-date-weekdays');
        const gridEl = calendar.querySelector('.report-date-grid');
        const prevBtn = calendar.querySelector('[data-nav="prev"]');
        const nextBtn = calendar.querySelector('[data-nav="next"]');
        const todayBtn = calendar.querySelector('.report-date-today-btn');

        WEEKDAYS.forEach((label) => {
            const span = document.createElement('span');
            span.textContent = label;
            weekdaysEl.appendChild(span);
        });

        for (let y = minDate.getFullYear(); y <= maxDate.getFullYear(); y++) {
            const option = document.createElement('option');
            option.value = String(y);
            option.textContent = String(y);
            yearSelect.appendChild(option);
        }

        MONTHS.forEach((name, index) => {
            const option = document.createElement('option');
            option.value = String(index);
            option.textContent = name;
            monthSelect.appendChild(option);
        });

        let viewYear = maxDate.getFullYear();
        let viewMonth = maxDate.getMonth();
        let isOpen = false;

        function syncDisplay() {
            if (input.value) {
                display.textContent = formatDisplay(input.value);
                display.classList.remove('is-placeholder');
            } else {
                display.textContent = 'Select date';
                display.classList.add('is-placeholder');
            }
        }

        function isDisabled(date) {
            const ymd = formatYmd(date);
            return ymd < minYmd || ymd > maxYmd;
        }

        function canShowMonth(year, month) {
            const first = formatYmd(startOfMonth(year, month));
            const last = formatYmd(new Date(year, month + 1, 0));
            return last >= minYmd && first <= maxYmd;
        }

        function updateNavState() {
            const prev = addMonths(viewYear, viewMonth, -1);
            const next = addMonths(viewYear, viewMonth, 1);
            prevBtn.disabled = !canShowMonth(prev.year, prev.month);
            nextBtn.disabled = !canShowMonth(next.year, next.month);
        }

        function renderGrid() {
            gridEl.innerHTML = '';
            monthSelect.value = String(viewMonth);
            yearSelect.value = String(viewYear);
            updateNavState();

            const firstDay = startOfMonth(viewYear, viewMonth);
            const leadingEmpty = firstDay.getDay();
            const daysInMonth = new Date(viewYear, viewMonth + 1, 0).getDate();
            const todayYmd = formatYmd(new Date());
            const selectedYmd = input.value;

            for (let i = 0; i < leadingEmpty; i++) {
                const empty = document.createElement('button');
                empty.type = 'button';
                empty.className = 'report-date-day is-empty';
                empty.tabIndex = -1;
                empty.disabled = true;
                gridEl.appendChild(empty);
            }

            for (let day = 1; day <= daysInMonth; day++) {
                const date = new Date(viewYear, viewMonth, day);
                const ymd = formatYmd(date);
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'report-date-day';
                btn.textContent = String(day);
                btn.dataset.ymd = ymd;

                if (ymd === todayYmd) btn.classList.add('is-today');
                if (ymd === selectedYmd) btn.classList.add('is-selected');
                if (isDisabled(date)) btn.disabled = true;

                btn.addEventListener('click', () => selectDate(ymd));
                gridEl.appendChild(btn);
            }
        }

        function selectDate(ymd) {
            input.value = ymd;
            input.dispatchEvent(new Event('change', { bubbles: true }));
            syncDisplay();
            closeCalendar();
        }

        function openCalendar() {
            if (isOpen) return;
            isOpen = true;
            calendar.classList.remove('hidden');
            display.setAttribute('aria-expanded', 'true');

            const selected = parseYmd(input.value);
            if (selected) {
                viewYear = selected.getFullYear();
                viewMonth = selected.getMonth();
            } else {
                viewYear = maxDate.getFullYear();
                viewMonth = maxDate.getMonth();
            }
            if (!canShowMonth(viewYear, viewMonth)) {
                viewYear = minDate.getFullYear();
                viewMonth = minDate.getMonth();
            }
            renderGrid();
        }

        function closeCalendar() {
            if (!isOpen) return;
            isOpen = false;
            calendar.classList.add('hidden');
            display.setAttribute('aria-expanded', 'false');
        }

        display.addEventListener('click', () => {
            if (isOpen) closeCalendar();
            else openCalendar();
        });

        prevBtn.addEventListener('click', () => {
            const prev = addMonths(viewYear, viewMonth, -1);
            if (!canShowMonth(prev.year, prev.month)) return;
            viewYear = prev.year;
            viewMonth = prev.month;
            renderGrid();
        });

        nextBtn.addEventListener('click', () => {
            const next = addMonths(viewYear, viewMonth, 1);
            if (!canShowMonth(next.year, next.month)) return;
            viewYear = next.year;
            viewMonth = next.month;
            renderGrid();
        });

        monthSelect.addEventListener('change', () => {
            viewMonth = Number(monthSelect.value);
            if (!canShowMonth(viewYear, viewMonth)) {
                viewMonth = minDate.getMonth();
                monthSelect.value = String(viewMonth);
            }
            renderGrid();
        });

        yearSelect.addEventListener('change', () => {
            viewYear = Number(yearSelect.value);
            if (!canShowMonth(viewYear, viewMonth)) {
                viewMonth = 0;
            }
            renderGrid();
        });

        todayBtn.addEventListener('click', () => {
            const today = formatYmd(new Date());
            if (today >= minYmd && today <= maxYmd) {
                selectDate(today);
            }
        });

        document.addEventListener('click', (event) => {
            if (!wrapper.contains(event.target)) closeCalendar();
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') closeCalendar();
        });

        input.addEventListener('change', syncDisplay);
        syncDisplay();

        input._reportDatePickerReset = () => {
            input.value = '';
            syncDisplay();
            closeCalendar();
        };
    }

    return {
        initAll(root = document) {
            root.querySelectorAll('input.report-date-input[type="date"]').forEach(init);
        },
        reset(input) {
            if (input && typeof input._reportDatePickerReset === 'function') {
                input._reportDatePickerReset();
            }
        },
    };
})();
