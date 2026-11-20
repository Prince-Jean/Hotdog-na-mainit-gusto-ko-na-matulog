<?php
declare(strict_types=1);
session_start();
require_once dirname(__DIR__) . '/app/bootstrap.php';

use App\Controllers\Authentication;
use App\Models\Client;
use App\Models\Trainer;
use App\Models\Schedule;
use App\Models\PaymentAndMessage;

Authentication::requireAuth(true);

$clientModel = new Client();
$trainerModel = new Trainer();
$scheduleModel = new Schedule();
$pmModel = new PaymentAndMessage();

$notice = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_message'])) {
    $pmModel->deleteContactMessage((int)$_POST['message_id']);
    $notice = 'Inquiry removed.';
}

$clients = $clientModel->getAllClients();
$trainers = $trainerModel->getAllTrainers();
$schedules = $scheduleModel->getAllSchedules();
$payments = $pmModel->getAllPayments();
$messages = $pmModel->getAllContactMessages();

$totalRevenue = array_sum(array_map(fn($p) => (float)($p['AmountPaid'] ?? 0), $payments));
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Admin Dashboard - BIG Gainz Fitness</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Montserrat:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,400&family=Orbitron:wght@800;900&family=Oswald:wght@700&family=Outfit:wght@400;600;800;900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    /* Theme tokens matching the portal aesthetic */
    :root {
      --bg-dark: #0a0a0c;
      --bg-sidebar: #18181a;
      --bg-card: #2D2D2F;
      --bg-card-hover: #38383a;
      --text-main: #ffffff;
      --text-muted: #a0a5b5;
      --accent-light: #e5e5e5;
      --font-logo: 'Impact', 'Haettenschweiler', 'Arial Narrow Bold', sans-serif;
      --font-btn-member: 'Oswald', sans-serif;
      --font-section-titles: 'Orbitron', sans-serif;
      --font-body-descriptions: 'Montserrat', sans-serif;
      --font-inter: 'Inter', sans-serif;
      --transition-fast: all 0.25s cubic-bezier(0.25, 0.8, 0.25, 1);
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

    /* Main sidebar + content layout */
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

    /* Mobile top bar and drawer overlay */
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

    /* Content panels, stats, and tables */
    .portal-content {
      flex: 1;
      min-width: 0;
      padding: 36px 32px 60px 32px;
      max-width: 1280px;
    }

    .portal-page-title {
      font-family: var(--font-section-titles) !important;
      font-weight: 900 !important;
      font-size: clamp(1.35rem, 2.5vw, 1.85rem);
      text-transform: uppercase;
      margin-bottom: 6px;
    }

    .portal-page-sub {
      color: var(--text-muted);
      font-size: 0.88rem;
      margin-bottom: 24px;
    }

    .stat-card {
      background: linear-gradient(145deg, #232428 0%, #141518 75%, #191a1e 100%);
      border: 1px solid rgba(255, 255, 255, 0.12);
      border-radius: 16px;
      padding: 20px 22px;
      height: 100%;
    }

    .stat-num {
      font-family: var(--font-section-titles) !important;
      font-weight: 900;
      font-size: 1.65rem;
      color: #ffffff;
      margin-bottom: 4px;
    }

    .stat-label {
      font-family: var(--font-inter) !important;
      font-size: 0.8rem;
      color: var(--text-muted);
    }

    .portal-panel {
      background: linear-gradient(145deg, #232428 0%, #141518 65%, #191a1e 100%);
      border: 1px solid rgba(255, 255, 255, 0.14);
      border-radius: 20px;
      padding: 26px 24px;
      box-shadow: 0 20px 50px rgba(0, 0, 0, 0.65);
      margin-bottom: 22px;
    }

    .panel-heading {
      font-family: var(--font-inter) !important;
      font-weight: 700;
      font-size: 0.95rem;
      color: #ffffff;
      margin-bottom: 14px;
    }

    .table-dark {
      --bs-table-bg: transparent;
      --bs-table-striped-bg: rgba(255, 255, 255, 0.02);
      --bs-table-hover-bg: rgba(255, 255, 255, 0.05);
      border-color: rgba(255, 255, 255, 0.08);
      color: #e5e7eb;
    }

    .table-dark th {
      font-family: var(--font-inter) !important;
      font-size: 0.76rem;
      text-transform: uppercase;
      letter-spacing: 0.6px;
      color: #9ca3af;
      border-bottom-color: rgba(255, 255, 255, 0.12);
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
        padding: 20px 16px;
      }
    }
  </style>
</head>
<body>
  <!-- Mobile header -->
  <div class="mobile-topbar">
    <a href="../index.php" class="sidebar-brand-mark" style="font-size:1.35rem;">
      <span>B<i class="fa-solid fa-dumbbell"></i>G</span>
    </a>
    <button type="button" class="mobile-menu-btn" id="sidebarToggle" aria-label="Toggle Menu">
      <i class="fa-solid fa-bars"></i>
    </button>
  </div>

  <div class="sidebar-backdrop" id="sidebarBackdrop"></div>

  <div class="portal-shell">
    <!-- Admin sidebar navigation -->
    <aside class="portal-sidebar" id="portalSidebar">
      <div>
        <a href="../index.php" class="sidebar-brand-box">
          <span class="sidebar-brand-mark">B<i class="fa-solid fa-dumbbell"></i>G</span>
          <span class="sidebar-brand-sub">GAINZFITNESS ADMIN</span>
        </a>
        <ul class="sidebar-nav-list">
          <li><a href="admin_dashboard.php" class="sidebar-nav-link active"><i class="fa-solid fa-border-all"></i><span>Overview</span></a></li>
          <li><a href="admin_clients.php" class="sidebar-nav-link"><i class="fa-solid fa-users"></i><span>Clients</span></a></li>
          <li><a href="admin_trainers.php" class="sidebar-nav-link"><i class="fa-solid fa-dumbbell"></i><span>Trainers</span></a></li>
          <li><a href="admin_schedules.php" class="sidebar-nav-link"><i class="fa-regular fa-calendar-check"></i><span>Schedules</span></a></li>
          <li><a href="admin_payments.php" class="sidebar-nav-link"><i class="fa-regular fa-credit-card"></i><span>Renewals</span></a></li>
        </ul>
      </div>
      <div class="sidebar-bottom">
        <a href="../logout.php" class="sidebar-logout-link"><i class="fa-solid fa-right-from-bracket"></i><span>Logout</span></a>
      </div>
    </aside>

    <!-- Main dashboard content -->
    <main class="portal-content">
      <h1 class="portal-page-title">DAGUPAN GYM OPERATIONS OVERVIEW</h1>
      <p class="portal-page-sub">Welcome, <?= htmlspecialchars($_SESSION['user_name'] ?? 'Administrator') ?>. Centralized records powered by MySQL, Merge Sort, and Binary Search.</p>

      <?php if ($notice): ?><div class="alert alert-success"><?= htmlspecialchars($notice) ?></div><?php endif; ?>

      <div class="row g-3 mb-4">
        <div class="col-sm-6 col-md-3"><div class="stat-card"><div class="stat-num"><?= count($clients) ?></div><div class="stat-label">Registered Members</div></div></div>
        <div class="col-sm-6 col-md-3"><div class="stat-card"><div class="stat-num"><?= count($trainers) ?></div><div class="stat-label">Active Coaches</div></div></div>
        <div class="col-sm-6 col-md-3"><div class="stat-card"><div class="stat-num"><?= count($schedules) ?></div><div class="stat-label">Workout Schedules</div></div></div>
        <div class="col-sm-6 col-md-3"><div class="stat-card"><div class="stat-num">&#8369;<?= number_format($totalRevenue, 2) ?></div><div class="stat-label">Recorded Payments</div></div></div>
      </div>

      <div class="portal-panel">
        <h2 class="panel-heading">Recent Contact Inquiries ("Connect to Our Team")</h2>
        <?php if (empty($messages)): ?>
          <p class="text-secondary small mb-0">No messages submitted yet.</p>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table table-dark table-striped align-middle small mb-0">
              <thead><tr><th>Name</th><th>Email</th><th>Phone</th><th>Message</th><th>Date</th><th>Action</th></tr></thead>
              <tbody>
                <?php foreach ($messages as $m): ?>
                  <tr>
                    <td><?= htmlspecialchars($m['FirstName'] . ' ' . $m['LastName']) ?></td>
                    <td><?= htmlspecialchars($m['Email']) ?></td>
                    <td><?= htmlspecialchars($m['PhoneNumber']) ?></td>
                    <td><?= htmlspecialchars($m['MessageBody']) ?></td>
                    <td><?= htmlspecialchars($m['SubmittedAt']) ?></td>
                    <td>
                      <form method="POST" onsubmit="return confirm('Delete this inquiry?');">
                        <input type="hidden" name="message_id" value="<?= (int)$m['MessageID'] ?>">
                        <button type="submit" name="delete_message" class="btn btn-sm btn-outline-danger">Del</button>
                      </form>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
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