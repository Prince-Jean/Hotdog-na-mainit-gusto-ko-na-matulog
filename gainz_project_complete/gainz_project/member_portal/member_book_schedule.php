<?php
declare(strict_types=1);
session_start();
require_once dirname(__DIR__) . '/app/bootstrap.php';

use App\Controllers\Authentication;
use App\Models\Schedule;
use App\Models\Trainer;

Authentication::requireAuth(false);

$scheduleModel = new Schedule();
$trainerModel = new Trainer();
$trainers = $trainerModel->getAllTrainers();
$notice = null;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['book_session'])) {
    $startDate = trim($_POST['schedule_start_date'] ?? '');
    $startTime = trim($_POST['schedule_start_time'] ?? '');
    $endDate = trim($_POST['schedule_end_date'] ?? '');
    $endTime = trim($_POST['schedule_end_time'] ?? '');

    $scheduleStart = ($startDate !== '' && $startTime !== '')
        ? $startDate . ' ' . (strlen($startTime) === 5 ? $startTime . ':00' : $startTime)
        : trim($_POST['schedule_start'] ?? '');

    $scheduleEnd = ($endDate !== '' && $endTime !== '')
        ? $endDate . ' ' . (strlen($endTime) === 5 ? $endTime . ':00' : $endTime)
        : trim($_POST['schedule_end'] ?? '');

    $res = $scheduleModel->createScheduleByEmail(
        $_SESSION['user_email'],
        (int)$_POST['trainer_id'],
        $scheduleStart,
        $scheduleEnd
    );
    if ($res['ok']) {
        $notice = $res['message'];
    } else {
        $error = $res['message'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Book Training Session - BIG Gainz Fitness</title>
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
      padding: 30px 28px;
      box-shadow: 0 20px 50px rgba(0, 0, 0, 0.65);
      max-width: 680px;
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
      color-scheme: dark;
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
      padding: 11px 24px;
      border-radius: 8px;
      border: none;
      letter-spacing: 1.5px;
      font-size: 0.95rem;
      cursor: pointer;
      transition: var(--transition-fast);
      display: inline-block;
    }

    .btn-portal-primary:hover {
      background-color: #ffffff;
      color: #000;
      transform: translateY(-2px);
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

      .portal-panel {
        padding: 22px 18px;
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
          <li><a href="member_profile.php" class="sidebar-nav-link"><i class="fa-regular fa-user"></i><span>My Account</span></a></li>
          <li><a href="member_schedules.php" class="sidebar-nav-link"><i class="fa-regular fa-calendar"></i><span>My Schedule</span></a></li>
          <li><a href="member_book_schedule.php" class="sidebar-nav-link active"><i class="fa-solid fa-circle-plus"></i><span>Create Schedule</span></a></li>
          <li><a href="member_payments.php" class="sidebar-nav-link"><i class="fa-regular fa-credit-card"></i><span>Payments</span></a></li>
        </ul>
      </div>
      <div class="sidebar-bottom">
        <a href="../logout.php" class="sidebar-logout-link"><i class="fa-solid fa-right-from-bracket"></i><span>Logout</span></a>
      </div>
    </aside>

    <main class="portal-content">
      <h1 class="portal-page-title">BOOK A TRAINING SESSION</h1>
      <?php if ($notice): ?><div class="alert alert-success"><?= htmlspecialchars($notice) ?></div><?php endif; ?>
      <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>

      <div class="portal-panel">
        <form method="POST" class="row g-3">
          <div class="col-12">
            <label class="form-label">Select Trainer</label>
            <select name="trainer_id" class="form-select" required>
              <?php foreach ($trainers as $t): ?>
                <option value="<?= (int)$t['TrainerID'] ?>"><?= htmlspecialchars($t['FirstName'] . ' ' . $t['LastName']) ?> (<?= htmlspecialchars($t['SpecializationType']) ?> &bull; &#8369;<?= number_format((float)$t['RatePerHour'], 2) ?>/hr)</option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-6"><label class="form-label">Start Date</label><input type="date" name="schedule_start_date" class="form-control" required></div>
          <div class="col-md-6"><label class="form-label">Start Time</label><input type="time" name="schedule_start_time" class="form-control" required></div>
          <div class="col-md-6"><label class="form-label">End Date</label><input type="date" name="schedule_end_date" class="form-control" required></div>
          <div class="col-md-6"><label class="form-label">End Time</label><input type="time" name="schedule_end_time" class="form-control" required></div>
          <div class="col-12"><button type="submit" name="book_session" class="btn-portal-primary">Confirm Booking</button></div>
        </form>
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