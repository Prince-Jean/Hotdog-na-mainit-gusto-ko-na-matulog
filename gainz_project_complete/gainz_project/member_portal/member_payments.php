<?php
declare(strict_types=1);
session_start();
require_once dirname(__DIR__) . '/app/bootstrap.php';

use App\Controllers\Authentication;
use App\Models\PaymentAndMessage;
use App\Models\Client;

Authentication::requireAuth(false);

$pmModel = new PaymentAndMessage();
$clientModel = new Client();

$profile = $clientModel->getClientByEmail($_SESSION['user_email']);
$payments = $pmModel->getPaymentsByEmail($_SESSION['user_email']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Payment History - BIG Gainz Fitness</title>
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
      font-size: clamp(1.35rem, 2.8vw, 1.95rem);
      text-transform: uppercase;
      margin-bottom: 6px;
    }

    .portal-sub-meta {
      font-family: var(--font-inter) !important;
      font-size: 0.85rem;
      color: var(--text-muted);
      margin-bottom: 24px;
    }

    .portal-panel {
      background: linear-gradient(145deg, #232428 0%, #141518 65%, #191a1e 100%);
      border: 1px solid rgba(255, 255, 255, 0.14);
      border-radius: 20px;
      padding: 26px 24px;
      box-shadow: 0 20px 50px rgba(0, 0, 0, 0.65);
    }

    .table-dark {
      --bs-table-bg: transparent;
      --bs-table-hover-bg: rgba(255, 255, 255, 0.04);
      --bs-table-border-color: rgba(255, 255, 255, 0.1);
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
          <li><a href="member_profile.php" class="sidebar-nav-link"><i class="fa-regular fa-user"></i><span>My Account</span></a></li>
          <li><a href="member_schedules.php" class="sidebar-nav-link"><i class="fa-regular fa-calendar"></i><span>My Schedule</span></a></li>
          <li><a href="member_book_schedule.php" class="sidebar-nav-link"><i class="fa-solid fa-circle-plus"></i><span>Create Schedule</span></a></li>
          <li><a href="member_payments.php" class="sidebar-nav-link active"><i class="fa-regular fa-credit-card"></i><span>Payments</span></a></li>
        </ul>
      </div>
      <div class="sidebar-bottom">
        <a href="../logout.php" class="sidebar-logout-link"><i class="fa-solid fa-right-from-bracket"></i><span>Logout</span></a>
      </div>
    </aside>

    <main class="portal-content">
      <h1 class="portal-page-title">MEMBERSHIP PAYMENT &amp; VALIDITY HISTORY</h1>
      <p class="portal-sub-meta">Current Plan: <strong><?= htmlspecialchars($profile['MembershipType'] ?? 'BASIC') ?></strong> &bull; Valid Until: <strong><?= htmlspecialchars($profile['MembershipExpiry'] ?? 'Active') ?></strong></p>

      <div class="portal-panel">
        <div class="table-responsive">
          <table class="table table-dark table-hover align-middle small mb-0">
            <thead><tr><th>ID</th><th>Plan</th><th>Cycle</th><th>Amount</th><th>Date Paid</th><th>Valid Until</th><th>Notes</th></tr></thead>
            <tbody>
              <?php if (empty($payments)): ?>
                <tr><td colspan="7" class="text-secondary">No payment transactions recorded yet. Renewals are logged manually at the Dagupan front desk.</td></tr>
              <?php else: ?>
                <?php foreach ($payments as $p): ?>
                  <tr>
                    <td><?= (int)$p['PaymentID'] ?></td>
                    <td><?= htmlspecialchars($p['MembershipType']) ?></td>
                    <td><?= htmlspecialchars($p['BillingCycle']) ?></td>
                    <td>&#8369;<?= number_format((float)$p['AmountPaid'], 2) ?></td>
                    <td><?= htmlspecialchars($p['PaymentDate']) ?></td>
                    <td><?= htmlspecialchars($p['ValidUntil']) ?></td>
                    <td><?= htmlspecialchars($p['PaymentNotes'] ?? '') ?></td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
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