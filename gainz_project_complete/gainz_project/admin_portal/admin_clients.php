<?php
declare(strict_types=1);
session_start();
require_once dirname(__DIR__) . '/app/bootstrap.php';

use App\Controllers\Authentication;
use App\Models\Client;
use App\Algorithms\SearchAndSort;

Authentication::requireAuth(true);

$clientModel = new Client();
$notice = null;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_client'])) {
        $ok = $clientModel->createClient(
            $_POST['last_name'] ?? '',
            $_POST['first_name'] ?? '',
            (int)($_POST['age'] ?? 18),
            $_POST['gender'] ?? 'M',
            (float)($_POST['weight'] ?? 60),
            (float)($_POST['height'] ?? 170),
            $_POST['membership_type'] ?? 'BASIC',
            $_POST['email'] ?? '',
            password_hash((string)($_POST['password'] ?? 'Gainz123!'), PASSWORD_DEFAULT),
            $_POST['phone'] ?? '',
            (int)($_POST['is_admin'] ?? 0)
        );
        $ok ? ($notice = "Member added successfully.") : ($error = "Failed to add member (check unique email/phone).");
    } elseif (isset($_POST['edit_client'])) {
        $ok = $clientModel->updateClientById(
            (int)$_POST['user_id'],
            $_POST['last_name'] ?? '',
            $_POST['first_name'] ?? '',
            (int)($_POST['age'] ?? 18),
            $_POST['gender'] ?? 'M',
            (float)($_POST['weight'] ?? 60),
            (float)($_POST['height'] ?? 170),
            $_POST['membership_type'] ?? 'BASIC',
            $_POST['email'] ?? '',
            $_POST['phone'] ?? '',
            (int)($_POST['is_admin'] ?? 0)
        );
        $ok ? ($notice = "Member record updated.") : ($error = "Failed to update member.");
    } elseif (isset($_POST['delete_client'])) {
        if ((int)$_POST['user_id'] === (int)($_SESSION['user_id'] ?? 0)) {
            $error = "You cannot delete your own currently logged-in admin account.";
        } else {
            $ok = $clientModel->deleteClient((int)$_POST['user_id']);
            $ok ? ($notice = "Member deleted.") : ($error = "Failed to delete member.");
        }
    }
}

$sortKey = $_GET['sort_by'] ?? 'FullName';
$sortDir = strtolower($_GET['dir'] ?? 'asc') === 'desc' ? 'desc' : 'asc';
$sortType = match ($sortKey) {
    'UserID', 'UserAge', 'UserWeight' => 'number',
    'RegistrationDate' => 'date',
    default => 'string',
};

$allClients = $clientModel->getAllClients();
$sortedClients = SearchAndSort::mergeSort($allClients, $sortKey, $sortDir, $sortType);

$searchQuery = trim($_GET['q'] ?? '');
$searchField = ($_GET['search_by'] ?? 'FullName') === 'UserID' ? 'UserID' : 'FullName';
$searchResult = null;
$displayedClients = $sortedClients;

if ($searchQuery !== '') {
    $prepForSearch = SearchAndSort::mergeSort(
        $allClients,
        $searchField,
        'asc',
        $searchField === 'UserID' ? 'number' : 'string'
    );
    $targetVal = $searchField === 'UserID' ? (int)$searchQuery : $searchQuery;
    $searchResult = SearchAndSort::binarySearch($prepForSearch, $searchField, $targetVal, $searchField === 'UserID' ? 'number' : 'string');
    $displayedClients = $searchResult['matches'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Manage Clients - BIG Gainz Fitness</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Montserrat:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,400&family=Orbitron:wght@800;900&family=Oswald:wght@700&family=Outfit:wght@400;600;800;900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    /* Shared portal theme styles */
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
      max-width: 1280px;
    }

    .portal-page-title {
      font-family: var(--font-section-titles) !important;
      font-weight: 900 !important;
      font-size: clamp(1.35rem, 2.5vw, 1.85rem);
      text-transform: uppercase;
      margin-bottom: 20px;
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

    .portal-panel .form-label {
      font-family: var(--font-inter) !important;
      font-size: 0.78rem;
      font-weight: 600;
      color: #d1d5db;
      margin-bottom: 4px;
    }

    .portal-panel .form-control,
    .portal-panel .form-select {
      background-color: #36373b;
      border: 1px solid rgba(255, 255, 255, 0.08);
      border-radius: 8px;
      color: #ffffff;
      font-family: var(--font-inter) !important;
      font-size: 0.85rem;
      padding: 8px 12px;
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
      padding: 8px 16px;
      border-radius: 8px;
      border: none;
      letter-spacing: 1px;
      font-size: 0.85rem;
      cursor: pointer;
      transition: var(--transition-fast);
    }

    .btn-portal-primary:hover {
      background-color: #ffffff;
      color: #000;
    }

    .table-dark {
      --bs-table-bg: transparent;
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
    <a href="admin_dashboard.php" class="sidebar-brand-mark" style="font-size:1.35rem;">
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
        <a href="admin_dashboard.php" class="sidebar-brand-box">
          <span class="sidebar-brand-mark">B<i class="fa-solid fa-dumbbell"></i>G</span>
          <span class="sidebar-brand-sub">GAINZFITNESS ADMIN</span>
        </a>
        <ul class="sidebar-nav-list">
          <li><a href="admin_dashboard.php" class="sidebar-nav-link"><i class="fa-solid fa-border-all"></i><span>Overview</span></a></li>
          <li><a href="admin_clients.php" class="sidebar-nav-link active"><i class="fa-solid fa-users"></i><span>Clients</span></a></li>
          <li><a href="admin_trainers.php" class="sidebar-nav-link"><i class="fa-solid fa-dumbbell"></i><span>Trainers</span></a></li>
          <li><a href="admin_schedules.php" class="sidebar-nav-link"><i class="fa-regular fa-calendar-check"></i><span>Schedules</span></a></li>
          <li><a href="admin_payments.php" class="sidebar-nav-link"><i class="fa-regular fa-credit-card"></i><span>Renewals</span></a></li>
        </ul>
      </div>
      <div class="sidebar-bottom">
        <a href="../logout.php" class="sidebar-logout-link"><i class="fa-solid fa-right-from-bracket"></i><span>Logout</span></a>
      </div>
    </aside>

    <!-- Main clients content -->
    <main class="portal-content">
      <h1 class="portal-page-title">MEMBER RECORDS (MERGE SORT &amp; BINARY SEARCH)</h1>

      <?php if ($notice): ?><div class="alert alert-success"><?= htmlspecialchars($notice) ?></div><?php endif; ?>
      <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>

      <!-- Search and sort controls -->
      <div class="portal-panel">
        <form method="GET" class="row g-2 align-items-end">
          <div class="col-md-3">
            <label class="form-label">Binary Search Key</label>
            <select name="search_by" class="form-select form-select-sm">
              <option value="FullName" <?= $searchField === 'FullName' ? 'selected' : '' ?>>Full Name (e.g. Wally Dela Cruz)</option>
              <option value="UserID" <?= $searchField === 'UserID' ? 'selected' : '' ?>>Member ID (e.g. 1)</option>
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label">Exact Lookup Target (O(log n))</label>
            <input type="text" name="q" value="<?= htmlspecialchars($searchQuery) ?>" class="form-control form-control-sm" placeholder="Enter exact name or ID...">
          </div>
          <div class="col-md-2">
            <label class="form-label">Merge Sort By</label>
            <select name="sort_by" class="form-select form-select-sm">
              <option value="FullName" <?= $sortKey === 'FullName' ? 'selected' : '' ?>>Name</option>
              <option value="UserID" <?= $sortKey === 'UserID' ? 'selected' : '' ?>>ID</option>
              <option value="MembershipType" <?= $sortKey === 'MembershipType' ? 'selected' : '' ?>>Plan</option>
              <option value="RegistrationDate" <?= $sortKey === 'RegistrationDate' ? 'selected' : '' ?>>Registered</option>
            </select>
          </div>
          <div class="col-md-1">
            <label class="form-label">Order</label>
            <select name="dir" class="form-select form-select-sm">
              <option value="asc" <?= $sortDir === 'asc' ? 'selected' : '' ?>>ASC</option>
              <option value="desc" <?= $sortDir === 'desc' ? 'selected' : '' ?>>DESC</option>
            </select>
          </div>
          <div class="col-md-2 d-flex gap-1">
            <button type="submit" class="btn-portal-primary w-100">Apply</button>
            <a href="admin_clients.php" class="btn btn-sm btn-outline-secondary d-flex align-items-center">Reset</a>
          </div>
        </form>

        <?php if ($searchResult !== null): ?>
          <div class="mt-3 small text-info">
            Binary Search on <strong><?= htmlspecialchars($searchField) ?></strong> for "<strong><?= htmlspecialchars($searchQuery) ?></strong>":
            found <strong><?= count($searchResult['matches']) ?></strong> match(es) in <strong><?= (int)$searchResult['comparisons'] ?></strong> comparison(s).
          </div>
        <?php endif; ?>
      </div>

      <!-- Register new member form -->
      <div class="portal-panel">
        <h2 class="panel-heading">Register New Member</h2>
        <form method="POST" class="row g-2">
          <div class="col-md-2"><input type="text" name="first_name" class="form-control form-control-sm" placeholder="First Name" required></div>
          <div class="col-md-2"><input type="text" name="last_name" class="form-control form-control-sm" placeholder="Last Name" required></div>
          <div class="col-md-1"><input type="number" name="age" class="form-control form-control-sm" placeholder="Age" required></div>
          <div class="col-md-1">
            <select name="gender" class="form-select form-select-sm">
              <option value="M">M</option><option value="F">F</option><option value="O">O</option>
            </select>
          </div>
          <div class="col-md-1"><input type="number" step="0.1" name="weight" class="form-control form-control-sm" placeholder="kg" required></div>
          <div class="col-md-1"><input type="number" step="0.1" name="height" class="form-control form-control-sm" placeholder="cm" required></div>
          <div class="col-md-2"><input type="email" name="email" class="form-control form-control-sm" placeholder="Email" required></div>
          <div class="col-md-2"><input type="text" name="phone" class="form-control form-control-sm" placeholder="Phone" required></div>
          <div class="col-md-2">
            <select name="membership_type" class="form-select form-select-sm">
              <option value="BASIC">BASIC</option><option value="MID">MID</option><option value="PRO">PRO</option>
            </select>
          </div>
          <div class="col-md-2"><input type="password" name="password" class="form-control form-control-sm" placeholder="Password" required></div>
          <div class="col-md-2">
            <select name="is_admin" class="form-select form-select-sm">
              <option value="0">Member</option><option value="1">Admin</option>
            </select>
          </div>
          <div class="col-md-2"><button type="submit" name="add_client" class="btn-portal-primary w-100">Add Member</button></div>
        </form>
      </div>

      <!-- Member records table -->
      <div class="portal-panel">
        <div class="table-responsive">
          <table class="table table-dark table-hover align-middle small mb-0">
            <thead>
              <tr>
                <th>ID</th><th>Name</th><th>Plan</th><th>Expiry</th><th>Email</th><th>Phone</th><th>Role</th><th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($displayedClients as $c): ?>
                <tr>
                  <td><?= (int)$c['UserID'] ?></td>
                  <td><?= htmlspecialchars($c['FirstName'] . ' ' . $c['LastName']) ?></td>
                  <td><span class="badge bg-secondary"><?= htmlspecialchars($c['MembershipType']) ?></span></td>
                  <td><?= htmlspecialchars($c['MembershipExpiry'] ?? 'N/A') ?></td>
                  <td><?= htmlspecialchars($c['UserEmail']) ?></td>
                  <td><?= htmlspecialchars($c['UserPhoneNo']) ?></td>
                  <td><?= (int)$c['isAdmin'] === 1 ? 'Admin' : 'Member' ?></td>
                  <td class="d-flex gap-1">
                    <button type="button" class="btn btn-sm btn-outline-light edit-trigger"
                      data-id="<?= (int)$c['UserID'] ?>"
                      data-fn="<?= htmlspecialchars($c['FirstName'], ENT_QUOTES) ?>"
                      data-ln="<?= htmlspecialchars($c['LastName'], ENT_QUOTES) ?>"
                      data-age="<?= (int)$c['UserAge'] ?>"
                      data-gender="<?= htmlspecialchars($c['UserGender'], ENT_QUOTES) ?>"
                      data-weight="<?= htmlspecialchars((string)$c['UserWeight'], ENT_QUOTES) ?>"
                      data-height="<?= htmlspecialchars((string)$c['UserHeight'], ENT_QUOTES) ?>"
                      data-plan="<?= htmlspecialchars($c['MembershipType'], ENT_QUOTES) ?>"
                      data-email="<?= htmlspecialchars($c['UserEmail'], ENT_QUOTES) ?>"
                      data-phone="<?= htmlspecialchars($c['UserPhoneNo'], ENT_QUOTES) ?>"
                      data-admin="<?= (int)$c['isAdmin'] ?>">Edit</button>
                    <form method="POST" onsubmit="return confirm('Delete this member?');">
                      <input type="hidden" name="user_id" value="<?= (int)$c['UserID'] ?>">
                      <button type="submit" name="delete_client" class="btn btn-sm btn-outline-danger">Del</button>
                    </form>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Hidden edit panel revealed when clicking Edit -->
      <div class="portal-panel" id="editMemberPanel" style="display:none;">
        <h2 class="panel-heading">Edit Selected Member</h2>
        <form method="POST" class="row g-2">
          <input type="hidden" name="user_id" id="editUserId">
          <div class="col-md-2"><input type="text" name="first_name" id="editFirstName" class="form-control form-control-sm" required></div>
          <div class="col-md-2"><input type="text" name="last_name" id="editLastName" class="form-control form-control-sm" required></div>
          <div class="col-md-1"><input type="number" name="age" id="editAge" class="form-control form-control-sm" required></div>
          <div class="col-md-1">
            <select name="gender" id="editGender" class="form-select form-select-sm">
              <option value="M">M</option><option value="F">F</option><option value="O">O</option>
            </select>
          </div>
          <div class="col-md-1"><input type="number" step="0.1" name="weight" id="editWeight" class="form-control form-control-sm" required></div>
          <div class="col-md-1"><input type="number" step="0.1" name="height" id="editHeight" class="form-control form-control-sm" required></div>
          <div class="col-md-2"><input type="email" name="email" id="editEmail" class="form-control form-control-sm" required></div>
          <div class="col-md-2"><input type="text" name="phone" id="editPhone" class="form-control form-control-sm" required></div>
          <div class="col-md-2">
            <select name="membership_type" id="editPlan" class="form-select form-select-sm">
              <option value="BASIC">BASIC</option><option value="MID">MID</option><option value="PRO">PRO</option>
            </select>
          </div>
          <div class="col-md-2">
            <select name="is_admin" id="editAdmin" class="form-select form-select-sm">
              <option value="0">Member</option><option value="1">Admin</option>
            </select>
          </div>
          <div class="col-md-2"><button type="submit" name="edit_client" class="btn btn-sm btn-success fw-bold w-100">Save Changes</button></div>
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

    document.addEventListener('DOMContentLoaded', () => {
      const panel = document.getElementById('editMemberPanel');
      document.querySelectorAll('.edit-trigger').forEach(btn => {
        btn.addEventListener('click', () => {
          document.getElementById('editUserId').value = btn.dataset.id;
          document.getElementById('editFirstName').value = btn.dataset.fn;
          document.getElementById('editLastName').value = btn.dataset.ln;
          document.getElementById('editAge').value = btn.dataset.age;
          document.getElementById('editGender').value = btn.dataset.gender;
          document.getElementById('editWeight').value = btn.dataset.weight;
          document.getElementById('editHeight').value = btn.dataset.height;
          document.getElementById('editEmail').value = btn.dataset.email;
          document.getElementById('editPhone').value = btn.dataset.phone;
          document.getElementById('editPlan').value = btn.dataset.plan;
          document.getElementById('editAdmin').value = btn.dataset.admin;
          panel.style.display = 'block';
          panel.scrollIntoView({ behavior: 'smooth' });
        });
      });
    });
  </script>
</body>
</html>