<?php
declare(strict_types=1);
session_start();
require_once dirname(__DIR__) . '/app/bootstrap.php';

use App\Controllers\Authentication;
use App\Models\Client;

Authentication::requireAuth(false);

$clientModel = new Client();
$notice = null;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) {
        $newEmail = trim($_POST['email'] ?? $_SESSION['user_email']);
        $ok = $clientModel->updateClientByEmail(
            $_SESSION['user_email'],
            $_POST['last_name'] ?? '',
            $_POST['first_name'] ?? '',
            (int)($_POST['age'] ?? 18),
            $_POST['gender'] ?? 'M',
            (float)($_POST['weight'] ?? 60),
            (float)($_POST['height'] ?? 170),
            $_POST['membership_type'] ?? 'BASIC',
            $newEmail,
            $_POST['phone'] ?? ''
        );
        if ($ok) {
            $_SESSION['user_email'] = $newEmail;
            $_SESSION['user_name'] = trim(($_POST['first_name'] ?? '') . ' ' . ($_POST['last_name'] ?? ''));
            $notice = "Profile updated successfully.";
        } else {
            $error = "Could not update profile (email or phone may already be in use).";
        }
    } elseif (isset($_POST['change_password'])) {
        $newPass = $_POST['new_password'] ?? '';
        $confirmPass = $_POST['confirm_password'] ?? '';
        if (strlen($newPass) < 6) {
            $error = "New password must be at least 6 characters.";
        } elseif ($newPass !== $confirmPass) {
            $error = "New passwords do not match.";
        } else {
            $ok = $clientModel->updatePasswordByEmail($_SESSION['user_email'], password_hash($newPass, PASSWORD_DEFAULT));
            $ok ? ($notice = "Password updated successfully.") : ($error = "Failed to update password.");
        }
    }
}

$profile = $clientModel->getClientByEmail($_SESSION['user_email']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>My Profile - BIG Gainz Fitness</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Montserrat:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,400&family=Orbitron:wght@800;900&family=Oswald:wght@700&family=Outfit:wght@400;600;800;900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    :root {
      --bg-dark: #0a0a0c;
      --bg-sidebar: #18181a;
      --bg-card: #2D2D2F;
      --bg-card-hover: #38383a;
      --text-main: #ffffff;
      --text-muted: #a0a5b5;
      --accent-light: #e5e5e5;
      --font-navbar: 'Malgun Gothic', 'Dotum', 'Apple SD Gothic Neo', sans-serif;
      --font-logo: 'Impact', 'Haettenschweiler', 'Arial Narrow Bold', sans-serif;
      --font-btn-member: 'Oswald', sans-serif;
      --font-section-titles: 'Orbitron', sans-serif;
      --font-body-descriptions: 'Montserrat', sans-serif;
      --font-inter: 'Inter', sans-serif;
      --transition-fast: all 0.25s cubic-bezier(0.25, 0.8, 0.25, 1);
      --transition-smooth: all 0.4s cubic-bezier(0.25, 0.8, 0.25, 1);
    }

    *, *::before, *::after {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }

    body {
      background-color: var(--bg-dark);
      color: var(--text-main);
      font-family: var(--font-body-descriptions);
      min-height: 100vh;
      overflow-x: hidden;
    }

    a {
      text-decoration: none;
      color: inherit;
    }

    .portal-shell {
      display: flex;
      min-height: 100vh;
      width: 100%;
    }

    .portal-sidebar {
      width: 240px;
      flex-shrink: 0;
      background-color: var(--bg-sidebar);
      border-right: 1px solid rgba(255, 255, 255, 0.07);
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      padding: 24px 16px 20px 16px;
      position: sticky;
      top: 0;
      height: 100vh;
      z-index: 1002;
      transition: transform 0.3s ease;
    }

    .sidebar-brand-box {
      display: flex;
      flex-direction: column;
      align-items: center;
      text-align: center;
      padding: 4px 8px 26px 8px;
    }

    .sidebar-brand-mark {
      font-family: var(--font-logo);
      font-size: 2rem;
      line-height: 1;
      letter-spacing: 3px;
      color: #ffffff;
      display: inline-flex;
      align-items: center;
      gap: 4px;
    }

    .sidebar-brand-sub {
      font-family: var(--font-section-titles) !important;
      font-size: 0.62rem;
      font-weight: 800;
      letter-spacing: 3px;
      color: #d1d5db;
      margin-top: 5px;
      text-transform: uppercase;
    }

    .sidebar-nav-list {
      list-style: none;
      display: flex;
      flex-direction: column;
      gap: 6px;
      padding: 0;
      margin: 0;
    }

    .sidebar-nav-link {
      display: flex;
      align-items: center;
      gap: 12px;
      padding: 11px 14px;
      border-radius: 8px;
      font-family: var(--font-inter) !important;
      font-size: 0.86rem;
      font-weight: 500;
      color: #c7cbd4;
      transition: var(--transition-fast);
    }

    .sidebar-nav-link i {
      width: 18px;
      text-align: center;
      font-size: 0.92rem;
      color: #9ca3af;
      transition: var(--transition-fast);
    }

    .sidebar-nav-link:hover {
      background-color: rgba(255, 255, 255, 0.06);
      color: #ffffff;
    }

    .sidebar-nav-link:hover i,
    .sidebar-nav-link.active i {
      color: #ffffff;
    }

    .sidebar-nav-link.active {
      background-color: rgba(255, 255, 255, 0.11);
      color: #ffffff;
      font-weight: 600;
    }

    .sidebar-bottom {
      padding-top: 14px;
      border-top: 1px solid rgba(255, 255, 255, 0.07);
    }

    .sidebar-logout-link {
      display: flex;
      align-items: center;
      gap: 12px;
      padding: 10px 14px;
      border-radius: 8px;
      font-family: var(--font-inter) !important;
      font-size: 0.86rem;
      font-weight: 500;
      color: #9ca3af;
      transition: var(--transition-fast);
    }

    .sidebar-logout-link i {
      width: 18px;
      text-align: center;
      font-size: 0.92rem;
    }

    .sidebar-logout-link:hover {
      background-color: rgba(220, 53, 69, 0.14);
      color: #ffb3ba;
    }

    .mobile-topbar {
      display: none;
      align-items: center;
      justify-content: space-between;
      padding: 12px 18px;
      background-color: var(--bg-sidebar);
      border-bottom: 1px solid rgba(255, 255, 255, 0.08);
      position: sticky;
      top: 0;
      z-index: 1001;
    }

    .mobile-menu-btn {
      background: transparent;
      border: 1px solid rgba(255, 255, 255, 0.16);
      color: #ffffff;
      border-radius: 6px;
      padding: 6px 10px;
      font-size: 1rem;
      cursor: pointer;
    }

    .sidebar-backdrop {
      display: none;
      position: fixed;
      inset: 0;
      background: rgba(0, 0, 0, 0.65);
      z-index: 1000;
    }

    .sidebar-backdrop.open {
      display: block;
    }

    .portal-content {
      flex: 1;
      min-width: 0;
      padding: 36px 32px 60px 32px;
      max-width: 1200px;
    }

    .portal-page-title {
      font-family: var(--font-section-titles) !important;
      font-weight: 900 !important;
      font-size: clamp(1.45rem, 2.8vw, 2rem);
      text-transform: uppercase;
      margin-bottom: 20px;
    }

    .portal-panel {
      background: linear-gradient(145deg, #232428 0%, #141518 65%, #191a1e 100%);
      border: 1px solid rgba(255, 255, 255, 0.14);
      border-radius: 20px;
      padding: 28px 26px;
      box-shadow: 0 20px 50px rgba(0, 0, 0, 0.65);
    }

    .portal-panel .form-label {
      font-family: var(--font-inter) !important;
      font-size: 0.82rem;
      font-weight: 600;
      color: #e5e7eb;
      margin-bottom: 6px;
    }

    .portal-panel .form-control,
    .portal-panel .form-select {
      background-color: #36373b;
      border: 1px solid rgba(255, 255, 255, 0.08);
      border-radius: 8px;
      color: #ffffff;
      font-family: var(--font-inter) !important;
      font-size: 0.88rem;
      padding: 10px 14px;
    }

    .portal-panel .form-control:focus,
    .portal-panel .form-select:focus {
      background-color: #3e3f44;
      border-color: rgba(255, 255, 255, 0.3);
      color: #ffffff;
      box-shadow: none;
    }

    .btn-portal-primary {
      font-family: var(--font-btn-member) !important;
      font-weight: 700 !important;
      text-transform: uppercase;
      background-color: var(--accent-light);
      color: #000;
      padding: 10px 22px;
      border-radius: 8px;
      border: none;
      letter-spacing: 1.5px;
      font-size: 0.9rem;
      cursor: pointer;
      transition: var(--transition-fast);
      display: inline-block;
    }

    .btn-portal-primary:hover {
      background-color: #ffffff;
      color: #000;
      transform: translateY(-2px);
    }

    .btn-portal-secondary {
      font-family: var(--font-inter) !important;
      font-weight: 700;
      background-color: #161619;
      color: #fff;
      padding: 10px 18px;
      border-radius: 8px;
      border: 1px solid rgba(255, 255, 255, 0.18);
      font-size: 0.85rem;
      cursor: pointer;
      transition: var(--transition-fast);
    }

    .btn-portal-secondary:hover {
      background-color: #ffffff;
      color: #000;
    }

    @media (max-width: 900px) {
      .portal-shell {
        flex-direction: column;
      }

      .mobile-topbar {
        display: flex;
      }

      .portal-sidebar {
        position: fixed;
        left: 0;
        top: 0;
        height: 100vh;
        transform: translateX(-100%);
      }

      .portal-sidebar.open {
        transform: translateX(0);
      }

      .portal-content {
        padding: 24px 18px 48px 18px;
      }
    }
  </style>
</head>
<body>
  <div class="mobile-topbar">
    <a href="member_dashboard.php" class="sidebar-brand-mark" style="font-size:1.35rem;">
      <span>B<i class="fa-solid fa-dumbbell"></i>G</span>
    </a>
    <button type="button" class="mobile-menu-btn" id="sidebarToggle" aria-label="Toggle Menu">
      <i class="fa-solid fa-bars"></i>
    </button>
  </div>

  <div class="sidebar-backdrop" id="sidebarBackdrop"></div>

  <div class="portal-shell">
    <aside class="portal-sidebar" id="portalSidebar">
      <div>
        <a href="member_dashboard.php" class="sidebar-brand-box">
          <span class="sidebar-brand-mark">B<i class="fa-solid fa-dumbbell"></i>G</span>
          <span class="sidebar-brand-sub">GAINZFITNESS</span>
        </a>
        <ul class="sidebar-nav-list">
          <li><a href="member_dashboard.php" class="sidebar-nav-link"><i class="fa-solid fa-border-all"></i><span>Dashboard</span></a></li>
          <li><a href="member_profile.php" class="sidebar-nav-link active"><i class="fa-regular fa-user"></i><span>My Account</span></a></li>
          <li><a href="member_schedules.php" class="sidebar-nav-link"><i class="fa-regular fa-calendar"></i><span>My Schedule</span></a></li>
          <li><a href="member_book_schedule.php" class="sidebar-nav-link"><i class="fa-solid fa-circle-plus"></i><span>Create Schedule</span></a></li>
          <li><a href="member_payments.php" class="sidebar-nav-link"><i class="fa-regular fa-credit-card"></i><span>Payments</span></a></li>
        </ul>
      </div>
      <div class="sidebar-bottom">
        <a href="../logout.php" class="sidebar-logout-link"><i class="fa-solid fa-right-from-bracket"></i><span>Logout</span></a>
      </div>
    </aside>

    <main class="portal-content">
      <h1 class="portal-page-title">MY MEMBER PROFILE</h1>
      <?php if ($notice): ?><div class="alert alert-success"><?= htmlspecialchars($notice) ?></div><?php endif; ?>
      <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>

      <div class="row g-4">
        <div class="col-lg-8">
          <div class="portal-panel h-100">
            <h2 class="h6 fw-bold mb-3">Personal &amp; Body Metrics</h2>
            <form method="POST" class="row g-3">
              <div class="col-md-6"><label class="form-label">First Name</label><input type="text" name="first_name" value="<?= htmlspecialchars($profile['FirstName'] ?? '') ?>" class="form-control" required></div>
              <div class="col-md-6"><label class="form-label">Last Name</label><input type="text" name="last_name" value="<?= htmlspecialchars($profile['LastName'] ?? '') ?>" class="form-control" required></div>
              <div class="col-md-4"><label class="form-label">Age</label><input type="number" name="age" value="<?= htmlspecialchars((string)($profile['UserAge'] ?? '')) ?>" class="form-control" required></div>
              <div class="col-md-4">
                <label class="form-label">Gender</label>
                <select name="gender" class="form-select">
                  <option value="M" <?= ($profile['UserGender'] ?? '') === 'M' ? 'selected' : '' ?>>Male</option>
                  <option value="F" <?= ($profile['UserGender'] ?? '') === 'F' ? 'selected' : '' ?>>Female</option>
                  <option value="O" <?= ($profile['UserGender'] ?? '') === 'O' ? 'selected' : '' ?>>Other</option>
                </select>
              </div>
              <div class="col-md-4">
                <label class="form-label">Membership Type</label>
                <select name="membership_type" class="form-select">
                  <option value="BASIC" <?= ($profile['MembershipType'] ?? '') === 'BASIC' ? 'selected' : '' ?>>BASIC</option>
                  <option value="MID" <?= ($profile['MembershipType'] ?? '') === 'MID' ? 'selected' : '' ?>>MID</option>
                  <option value="PRO" <?= ($profile['MembershipType'] ?? '') === 'PRO' ? 'selected' : '' ?>>PRO</option>
                </select>
              </div>
              <div class="col-md-6"><label class="form-label">Weight (kg)</label><input type="number" step="0.1" name="weight" value="<?= htmlspecialchars((string)($profile['UserWeight'] ?? '')) ?>" class="form-control" required></div>
              <div class="col-md-6"><label class="form-label">Height (cm)</label><input type="number" step="0.1" name="height" value="<?= htmlspecialchars((string)($profile['UserHeight'] ?? '')) ?>" class="form-control" required></div>
              <div class="col-md-6"><label class="form-label">Email</label><input type="email" name="email" value="<?= htmlspecialchars($profile['UserEmail'] ?? '') ?>" class="form-control" required></div>
              <div class="col-md-6"><label class="form-label">Phone Number</label><input type="text" name="phone" value="<?= htmlspecialchars($profile['UserPhoneNo'] ?? '') ?>" class="form-control" required></div>
              <div class="col-12"><button type="submit" name="update_profile" class="btn-portal-primary">Update Profile</button></div>
            </form>
          </div>
        </div>

        <div class="col-lg-4">
          <div class="portal-panel h-100">
            <h2 class="h6 fw-bold mb-3">Security &amp; Password</h2>
            <form method="POST" class="d-grid gap-3">
              <div>
                <label class="form-label">New Password</label>
                <input type="password" name="new_password" class="form-control" required>
              </div>
              <div>
                <label class="form-label">Confirm New Password</label>
                <input type="password" name="confirm_password" class="form-control" required>
              </div>
              <button type="submit" name="change_password" class="btn-portal-secondary">Change Password</button>
            </form>
          </div>
        </div>
      </div>
    </main>
  </div>

  <script>
    const sidebar = document.getElementById('portalSidebar');
    const toggle = document.getElementById('sidebarToggle');
    const backdrop = document.getElementById('sidebarBackdrop');
    if (toggle && sidebar && backdrop) {
      toggle.addEventListener('click', () => {
        sidebar.classList.toggle('open');
        backdrop.classList.toggle('open');
      });
      backdrop.addEventListener('click', () => {
        sidebar.classList.remove('open');
        backdrop.classList.remove('open');
      });
    }
  </script>
</body>
</html>