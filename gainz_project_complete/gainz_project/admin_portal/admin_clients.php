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
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=Montserrat:wght@400;600;700;800&family=Outfit:wght@700;800;900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
  <nav class="topbar d-flex flex-wrap justify-content-between align-items-center gap-3">
    <a href="admin_dashboard.php" class="text-white fw-bold text-decoration-none"><i class="fa-solid fa-dumbbell text-danger me-2"></i>BIG GainzFitness Admin</a>
    <div class="d-flex flex-wrap gap-2">
      <a href="admin_dashboard.php" class="btn btn-sm btn-outline-light">Overview</a>
      <a href="admin_clients.php" class="btn btn-sm btn-light fw-bold">Clients</a>
      <a href="admin_trainers.php" class="btn btn-sm btn-outline-light">Trainers</a>
      <a href="admin_schedules.php" class="btn btn-sm btn-outline-light">Schedules</a>
      <a href="admin_payments.php" class="btn btn-sm btn-outline-light">Renewals</a>
      <a href="../logout.php" class="btn btn-sm btn-danger">Logout</a>
    </div>
  </nav>

  <main class="container py-4">
    <h1 class="h3 fw-bold mb-3">MEMBER RECORDS (MERGE SORT &amp; BINARY SEARCH)</h1>

    <?php if ($notice): ?><div class="alert alert-success"><?= htmlspecialchars($notice) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>

    <div class="panel">
      <form method="GET" class="row g-2 align-items-end">
        <div class="col-md-3">
          <label class="form-label small text-secondary">Binary Search Key</label>
          <select name="search_by" class="form-select form-select-sm bg-dark text-white border-secondary">
            <option value="FullName" <?= $searchField === 'FullName' ? 'selected' : '' ?>>Full Name (e.g. Wally Dela Cruz)</option>
            <option value="UserID" <?= $searchField === 'UserID' ? 'selected' : '' ?>>Member ID (e.g. 1)</option>
          </select>
        </div>
        <div class="col-md-4">
          <label class="form-label small text-secondary">Exact Lookup Target (O(log n))</label>
          <input type="text" name="q" value="<?= htmlspecialchars($searchQuery) ?>" class="form-control form-control-sm bg-dark text-white border-secondary" placeholder="Enter exact name or ID...">
        </div>
        <div class="col-md-2">
          <label class="form-label small text-secondary">Merge Sort By</label>
          <select name="sort_by" class="form-select form-select-sm bg-dark text-white border-secondary">
            <option value="FullName" <?= $sortKey === 'FullName' ? 'selected' : '' ?>>Name</option>
            <option value="UserID" <?= $sortKey === 'UserID' ? 'selected' : '' ?>>ID</option>
            <option value="MembershipType" <?= $sortKey === 'MembershipType' ? 'selected' : '' ?>>Plan</option>
            <option value="RegistrationDate" <?= $sortKey === 'RegistrationDate' ? 'selected' : '' ?>>Registered</option>
          </select>
        </div>
        <div class="col-md-1">
          <label class="form-label small text-secondary">Order</label>
          <select name="dir" class="form-select form-select-sm bg-dark text-white border-secondary">
            <option value="asc" <?= $sortDir === 'asc' ? 'selected' : '' ?>>ASC</option>
            <option value="desc" <?= $sortDir === 'desc' ? 'selected' : '' ?>>DESC</option>
          </select>
        </div>
        <div class="col-md-2 d-flex gap-1">
          <button type="submit" class="btn btn-sm btn-accent fw-bold w-100">Apply</button>
          <a href="admin_clients.php" class="btn btn-sm btn-outline-secondary">Reset</a>
        </div>
      </form>

      <?php if ($searchResult !== null): ?>
        <div class="mt-3 small text-info">
          Binary Search on <strong><?= htmlspecialchars($searchField) ?></strong> for "<strong><?= htmlspecialchars($searchQuery) ?></strong>":
          found <strong><?= count($searchResult['matches']) ?></strong> match(es) in <strong><?= (int)$searchResult['comparisons'] ?></strong> comparison(s).
        </div>
      <?php endif; ?>
    </div>

    <div class="panel">
      <h2 class="h6 fw-bold mb-3">Register New Member</h2>
      <form method="POST" class="row g-2">
        <div class="col-md-2"><input type="text" name="first_name" class="form-control form-control-sm bg-dark text-white border-secondary" placeholder="First Name" required></div>
        <div class="col-md-2"><input type="text" name="last_name" class="form-control form-control-sm bg-dark text-white border-secondary" placeholder="Last Name" required></div>
        <div class="col-md-1"><input type="number" name="age" class="form-control form-control-sm bg-dark text-white border-secondary" placeholder="Age" required></div>
        <div class="col-md-1">
          <select name="gender" class="form-select form-select-sm bg-dark text-white border-secondary">
            <option value="M">M</option><option value="F">F</option><option value="O">O</option>
          </select>
        </div>
        <div class="col-md-1"><input type="number" step="0.1" name="weight" class="form-control form-control-sm bg-dark text-white border-secondary" placeholder="kg" required></div>
        <div class="col-md-1"><input type="number" step="0.1" name="height" class="form-control form-control-sm bg-dark text-white border-secondary" placeholder="cm" required></div>
        <div class="col-md-2"><input type="email" name="email" class="form-control form-control-sm bg-dark text-white border-secondary" placeholder="Email" required></div>
        <div class="col-md-2"><input type="text" name="phone" class="form-control form-control-sm bg-dark text-white border-secondary" placeholder="Phone" required></div>
        <div class="col-md-2">
          <select name="membership_type" class="form-select form-select-sm bg-dark text-white border-secondary">
            <option value="BASIC">BASIC</option><option value="MID">MID</option><option value="PRO">PRO</option>
          </select>
        </div>
        <div class="col-md-2"><input type="password" name="password" class="form-control form-control-sm bg-dark text-white border-secondary" placeholder="Password" required></div>
        <div class="col-md-2">
          <select name="is_admin" class="form-select form-select-sm bg-dark text-white border-secondary">
            <option value="0">Member</option><option value="1">Admin</option>
          </select>
        </div>
        <div class="col-md-2"><button type="submit" name="add_client" class="btn btn-sm btn-accent fw-bold w-100">Add Member</button></div>
      </form>
    </div>

    <div class="panel">
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

    <div class="panel" id="editMemberPanel" style="display:none;">
      <h2 class="h6 fw-bold mb-3">Edit Selected Member</h2>
      <form method="POST" class="row g-2">
        <input type="hidden" name="user_id" id="editUserId">
        <div class="col-md-2"><input type="text" name="first_name" id="editFirstName" class="form-control form-control-sm bg-dark text-white border-secondary" required></div>
        <div class="col-md-2"><input type="text" name="last_name" id="editLastName" class="form-control form-control-sm bg-dark text-white border-secondary" required></div>
        <div class="col-md-1"><input type="number" name="age" id="editAge" class="form-control form-control-sm bg-dark text-white border-secondary" required></div>
        <div class="col-md-1">
          <select name="gender" id="editGender" class="form-select form-select-sm bg-dark text-white border-secondary">
            <option value="M">M</option><option value="F">F</option><option value="O">O</option>
          </select>
        </div>
        <div class="col-md-1"><input type="number" step="0.1" name="weight" id="editWeight" class="form-control form-control-sm bg-dark text-white border-secondary" required></div>
        <div class="col-md-1"><input type="number" step="0.1" name="height" id="editHeight" class="form-control form-control-sm bg-dark text-white border-secondary" required></div>
        <div class="col-md-2"><input type="email" name="email" id="editEmail" class="form-control form-control-sm bg-dark text-white border-secondary" required></div>
        <div class="col-md-2"><input type="text" name="phone" id="editPhone" class="form-control form-control-sm bg-dark text-white border-secondary" required></div>
        <div class="col-md-2">
          <select name="membership_type" id="editPlan" class="form-select form-select-sm bg-dark text-white border-secondary">
            <option value="BASIC">BASIC</option><option value="MID">MID</option><option value="PRO">PRO</option>
          </select>
        </div>
        <div class="col-md-2">
          <select name="is_admin" id="editAdmin" class="form-select form-select-sm bg-dark text-white border-secondary">
            <option value="0">Member</option><option value="1">Admin</option>
          </select>
        </div>
        <div class="col-md-2"><button type="submit" name="edit_client" class="btn btn-sm btn-success fw-bold w-100">Save Changes</button></div>
      </form>
    </div>
  </main>

  <script>
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
