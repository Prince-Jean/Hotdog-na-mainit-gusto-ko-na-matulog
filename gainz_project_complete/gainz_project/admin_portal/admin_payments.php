<?php
declare(strict_types=1);
session_start();
require_once dirname(__DIR__) . '/app/bootstrap.php';

use App\Controllers\Authentication;
use App\Models\PaymentAndMessage;
use App\Models\Client;
use App\Algorithms\SearchAndSort;

Authentication::requireAuth(true);

$pmModel = new PaymentAndMessage();
$clientModel = new Client();
$notice = null;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['record_renewal'])) {
    $ok = $pmModel->recordPaymentRenewal(
        (int)$_POST['user_id'],
        $_POST['membership_type'] ?? 'BASIC',
        $_POST['billing_cycle'] ?? 'MONTHLY',
        (float)($_POST['amount_paid'] ?? 0),
        $_POST['notes'] ?? 'Recorded manually by admin'
    );
    $ok ? ($notice = "Payment renewal recorded and member validity updated.") : ($error = "Failed to record renewal.");
}

$sortBy = $_GET['sort_by'] ?? 'PaymentDate';
$dir = strtolower($_GET['dir'] ?? 'desc') === 'asc' ? 'asc' : 'desc';
$type = match ($sortBy) {
    'AmountPaid' => 'number',
    'PaymentDate', 'ValidUntil' => 'date',
    default => 'string',
};

$payments = SearchAndSort::mergeSort($pmModel->getAllPayments(), $sortBy, $dir, $type);
$clients = $clientModel->getAllClients();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Membership Renewals - BIG Gainz Fitness</title>
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
      <a href="admin_clients.php" class="btn btn-sm btn-outline-light">Clients</a>
      <a href="admin_trainers.php" class="btn btn-sm btn-outline-light">Trainers</a>
      <a href="admin_schedules.php" class="btn btn-sm btn-outline-light">Schedules</a>
      <a href="admin_payments.php" class="btn btn-sm btn-light fw-bold">Renewals</a>
      <a href="../logout.php" class="btn btn-sm btn-danger">Logout</a>
    </div>
  </nav>

  <main class="container py-4">
    <h1 class="h3 fw-bold mb-3">MANUAL PAYMENT RENEWALS (MONTHLY / BI-ANNUAL / ANNUAL)</h1>
    <?php if ($notice): ?><div class="alert alert-success"><?= htmlspecialchars($notice) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>

    <div class="panel">
      <h2 class="h6 fw-bold mb-3">Record Front-Desk / Direct Renewal</h2>
      <form method="POST" class="row g-2">
        <div class="col-md-3">
          <select name="user_id" class="form-select form-select-sm bg-dark text-white border-secondary" required>
            <?php foreach ($clients as $c): ?>
              <option value="<?= (int)$c['UserID'] ?>"><?= htmlspecialchars($c['FirstName'] . ' ' . $c['LastName']) ?> (<?= htmlspecialchars($c['MembershipType']) ?>)</option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-2">
          <select name="membership_type" class="form-select form-select-sm bg-dark text-white border-secondary">
            <option value="BASIC">BASIC</option><option value="MID">MID</option><option value="PRO">PRO</option>
          </select>
        </div>
        <div class="col-md-2">
          <select name="billing_cycle" class="form-select form-select-sm bg-dark text-white border-secondary">
            <option value="MONTHLY">MONTHLY (30d)</option>
            <option value="BIANNUAL">BIANNUAL (180d)</option>
            <option value="ANNUAL">ANNUAL (365d)</option>
          </select>
        </div>
        <div class="col-md-2"><input type="number" step="0.01" name="amount_paid" class="form-control form-control-sm bg-dark text-white border-secondary" placeholder="Amount (₱)" required></div>
        <div class="col-md-2"><input type="text" name="notes" class="form-control form-control-sm bg-dark text-white border-secondary" placeholder="Receipt / Notes"></div>
        <div class="col-md-1"><button type="submit" name="record_renewal" class="btn btn-sm btn-accent fw-bold w-100">Save</button></div>
      </form>
    </div>

    <div class="panel">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="h6 fw-bold mb-0">Payment Ledger (Merge Sort)</h2>
        <div class="d-flex gap-2">
          <a href="?sort_by=PaymentDate&dir=desc" class="btn btn-sm btn-outline-light">Latest Date</a>
          <a href="?sort_by=AmountPaid&dir=desc" class="btn btn-sm btn-outline-light">Highest Amount</a>
        </div>
      </div>
      <div class="table-responsive">
        <table class="table table-dark table-hover align-middle small mb-0">
          <thead><tr><th>ID</th><th>Member</th><th>Plan</th><th>Cycle</th><th>Amount</th><th>Payment Date</th><th>Valid Until</th><th>Notes</th></tr></thead>
          <tbody>
            <?php foreach ($payments as $p): ?>
              <tr>
                <td><?= (int)$p['PaymentID'] ?></td>
                <td><?= htmlspecialchars($p['MemberName']) ?></td>
                <td><?= htmlspecialchars($p['MembershipType']) ?></td>
                <td><?= htmlspecialchars($p['BillingCycle']) ?></td>
                <td>&#8369;<?= number_format((float)$p['AmountPaid'], 2) ?></td>
                <td><?= htmlspecialchars($p['PaymentDate']) ?></td>
                <td><?= htmlspecialchars($p['ValidUntil']) ?></td>
                <td><?= htmlspecialchars($p['PaymentNotes'] ?? '') ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </main>
</body>
</html>
