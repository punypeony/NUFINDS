const departments = [
  'COLLEGE OF ALLIED HEALTH',
  'COLLEGE OF ARCHITECTURE',
  'COLLEGE OF BUSINESS AND ACCOUNTANCY',
  'COLLEGE OF COMPUTING AND INFORMATION TECHNOLOGIES',
  'COLLEGE OF EDUCATION ARTS AND SCIENCES',
  'COLLEGE OF ENGINEERING',
  'COLLEGE OF TOURISM AND HOSPITALITY MANAGEMENT'
];

document.addEventListener('DOMContentLoaded', () => {
  const departmentSelect = document.getElementById('department-select');
  if (!departmentSelect) return;

  departments.forEach(department => {
    const option = document.createElement('option');
    option.value = department;
    option.textContent = department;
    departmentSelect.appendChild(option);
  });
});
