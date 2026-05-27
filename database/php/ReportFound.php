<?php
require_once __DIR__ . '/lib/Database.php';
require_once __DIR__ . '/lib/SessionHelper.php';

SessionHelper::requireLogin('../../pages/login.html');

$studentNumber     = SessionHelper::get('StudentNumber', '');
$studentEmail      = SessionHelper::get('StudentEmail', '');
$collegeDepartment = SessionHelper::get('CollegeDepartment', '');

$conn = Database::connect();
$stmt = $conn->prepare('SELECT StudentEmail, CollegeDepartment FROM studentinfo WHERE StudentNumber = ?');
if ($stmt) {
    $stmt->bind_param('s', $studentNumber);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result && $result->num_rows === 1) {
        $row               = $result->fetch_assoc();
        $studentEmail      = $row['StudentEmail']      ?: $studentEmail;
        $collegeDepartment = $row['CollegeDepartment'] ?: $collegeDepartment;
    }
    $stmt->close();
}

$displayEmail             = htmlspecialchars($studentEmail      ?: 'Student',           ENT_QUOTES, 'UTF-8');
$displayStudentNumber     = htmlspecialchars($studentNumber,                             ENT_QUOTES, 'UTF-8');
$displayCollegeDepartment = htmlspecialchars($collegeDepartment ?: 'College Department', ENT_QUOTES, 'UTF-8');
$profileEmail             = htmlspecialchars($studentEmail      ?: 'userloggedin@students.national-u.edu.ph', ENT_QUOTES, 'UTF-8');
$todayDate                = date('Y-m-d');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>NU Finds - Report Found Item</title>
  <link rel="stylesheet" href="../../css/ReportFound.css">
  <style>
    .popup { position: fixed; inset: 0; display: flex; align-items: center; justify-content: center; background: rgba(0,0,0,0.45); z-index: 9999; }
    .popup.hidden { display: none; }
    .popup-content { background: white; padding: 1.5rem; border-radius: 14px; width: min(95%,420px); text-align: center; box-shadow: 0 20px 40px rgba(0,0,0,0.2); border-top: 5px solid transparent; }
    .popup-content.success { border-top-color: #f2c100; }
    .popup-content.error   { border-top-color: #b00020; }
    .popup-content.warning { border-top-color: #f2c100; }
    .popup-content h2 { margin-bottom: 1rem; color: #25358c; }
    .popup-content.error h2   { color: #b00020; }
    .popup-content.warning h2 { color: #e6a800; }
    .popup-content p  { margin-bottom: 1.25rem; color: #333; }
    #popup-buttons { display: flex; justify-content: center; gap: 12px; }
    #popup-buttons button { background: #25358c; color: #f2c100; border: none; border-radius: 10px; padding: 0.75rem 1.25rem; cursor: pointer; }
    #popup-cancel { background: #ccc !important; color: #333 !important; }
    .remove-image-btn { position: absolute; top: 8px; right: 8px; background: #b00020; color: white; border: none; border-radius: 50%; width: 26px; height: 26px; font-size: 14px; cursor: pointer; display: flex; align-items: center; justify-content: center; z-index: 10; }
  </style>
</head>
<body>

<div class="container">

  <div class="topbar">
                <img src="../../assets/images/nufindslogo white.png" alt="NU Finds White Logo" class="logo-header">
    <div class="topbar-user"
         data-user-email="<?= $profileEmail ?>"
         data-student-number="<?= $displayStudentNumber ?>"
         data-college-dept="<?= $displayCollegeDepartment ?>">
      <div class="user-info">Hi, <span class="user-email"><?= $displayEmail ?></span></div>
      <div class="profile-menu">
        <img src="../../assets/images/profileicon.png" alt="Profile icon" class="profile-icon" id="profileToggle">
        <div class="profile-dropdown" id="profileDropdown">
          <div class="profile-dropdown-header">Account</div>
          <div class="profile-dropdown-item"><span>Student No</span><span class="dropdown-student-number"><?= $displayStudentNumber ?></span></div>
          <div class="profile-dropdown-item"><span>Email</span><span class="dropdown-email"><?= $profileEmail ?></span></div>
          <div class="profile-dropdown-item"><span>College Dept</span><span class="dropdown-college-dept"><?= $displayCollegeDepartment ?></span></div>
          <button class="logout-btn" id="logoutBtn">Logout</button>
        </div>
      </div>
    </div>
  </div>

            <a href="home.php" class="back-icon">
    <img src="../../assets/images/back.png" alt="Back">
  </a>

  <section class="hero">
    <div class="building"></div>
    <div class="main-container">

      <h2>Report a Found Item</h2>

      <form action="ReportSubmission.php" method="post" id="report-form" enctype="multipart/form-data">
        <input type="hidden" name="report_type" value="found">
        <div class="input-row">
          <div class="input-box">
            <img src="../../assets/images/location.png" alt="Location Icon">
            <select id="location-select" required aria-label="Location">
              <option value="" disabled selected>Select location</option>
              <option value="MB">MB</option>
              <option value="GARDEN">GARDEN</option>
              <option value="OPEN COURT">OPEN COURT</option>
              <option value="CRUCIFIX">CRUCIFIX</option>
              <option value="JMB">JMB</option>
              <option value="ANNEX I">ANNEX I</option>
              <option value="ANNEX II">ANNEX II</option>
              <option value="PARKING">PARKING</option>
            </select>
            <select id="floor-select" style="display:none; margin-top:10px;" aria-label="Floor"></select>
            <input type="hidden" name="Location" id="Location">
          </div>
          <div class="input-box">
            <img src="../../assets/images/date.png" alt="Date Icon">
            <input type="date" name="DateFound" required max="<?= $todayDate ?>">
          </div>
        </div>

        <div class="section-title">Select a category</div>
        <input type="hidden" name="Category" id="Category" required>
        <div class="category-container">
          <button type="button" class="category-btn" data-category="Wallet/Credit Card/Money"><img src="../../assets/images/wallet.png" alt="Wallet"><span>Wallet/Credit Card/Money</span></button>
          <button type="button" class="category-btn" data-category="Identity Document"><img src="../../assets/images/id.png" alt="ID"><span>Identity Document</span></button>
          <button type="button" class="category-btn" data-category="Bag"><img src="../../assets/images/bag.png" alt="Bag"><span>Bag</span></button>
          <button type="button" class="category-btn" data-category="Electronics/Gadgets"><img src="../../assets/images/electronics.png" alt="Electronics"><span>Electronics/Gadgets</span></button>
          <button type="button" class="category-btn" data-category="Accessories"><img src="../../assets/images/accessories.png" alt="Accessories"><span>Accessories</span></button>
          <button type="button" class="category-btn" data-category="Others"><img src="../../assets/images/others.png" alt="Others"><span>Others</span></button>
        </div>

        <div class="section-title">Describe your item</div>
        <textarea name="Description" placeholder="Describe the object and its distinctive elements as well as possible. Do not indicate first name, last name, surname, address or number." required></textarea>

        <div class="section-title" style="margin-top:28px;">
          Upload a Photo <span class="optional-label">(optional)</span>
        </div>
        <div class="upload-box" id="upload-box" style="position:relative;">
          <input type="file" name="ItemImage" id="item-image" accept="image/*">
          <label for="item-image" class="upload-label">
            <svg class="upload-icon-svg" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
              <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
              <polyline points="17 8 12 3 7 8"/>
              <line x1="12" y1="3" x2="12" y2="15"/>
            </svg>
            <span id="upload-text">Click to upload image</span>
          </label>
          <img id="image-preview" class="image-preview hidden" alt="Preview">
          <button type="button" id="remove-image" class="remove-image-btn hidden">&#10005;</button>
        </div>

          <button type="submit" class="submit-btn">Submit</button>
      </form>

    </div>
  </section>
</div>

<div id="popup-overlay" class="popup hidden">
  <div class="popup-content" id="popup-content">
    <h2 id="popup-title">Message</h2>
    <p id="popup-message"></p>
    <div id="popup-buttons">
      <button id="popup-ok">OK</button>
      <button id="popup-cancel" class="hidden">Cancel</button>
    </div>
  </div>
</div>

<script src="../../js/report-form.js"></script>
<script>
(function () {
  const profileToggle   = document.getElementById('profileToggle');
  const profileDropdown = document.getElementById('profileDropdown');
  profileToggle.addEventListener('click', e => { e.stopPropagation(); profileDropdown.classList.toggle('open'); });
  document.addEventListener('click', () => profileDropdown.classList.remove('open'));
  document.getElementById('logoutBtn').addEventListener('click', () => window.location.href = 'logout.php');
})();
</script>
</body>
</html>

