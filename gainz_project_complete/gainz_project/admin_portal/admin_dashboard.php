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
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=Montserrat:wght@400;600;700;800&family=Outfit:wght@700;800;900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
  <nav class="topbar d-flex flex-wrap justify-content-between align-items-center gap-3">
    <a href="../index.php" class="text-white fw-bold text-decoration-none"><i class="fa-solid fa-dumbbell text-danger me-2"></i>BIG GainzFitness Admin</a>
    <div class="d-flex flex-wrap gap-2">
      <a href="admin_dashboard.php" class="btn btn-sm btn-light fw-bold">Overview</a>
      <a href="admin_clients.php" class="btn btn-sm btn-outline-light">Clients</a>
      <a href="admin_trainers.php" class="btn btn-sm btn-outline-light">Trainers</a>
      <a href="admin_schedules.php" class="btn btn-sm btn-outline-light">Schedules</a>
      <a href="admin_payments.php" class="btn btn-sm btn-outline-light">Renewals</a>
      <a href="../logout.php" class="btn btn-sm btn-danger">Logout</a>
    </div>
  </nav>

  <main class="container py-4">
    <h1 class="h3 fw-bold mb-1">DAGUPAN GYM OPERATIONS OVERVIEW</h1>
    <p class="text-secondary small mb-4">Welcome, <?= htmlspecialchars($_SESSION['user_name'] ?? 'Administrator') ?>. Centralized records powered by MySQL, Merge Sort, and Binary Search.</p>

    <?php if ($notice): ?><div class="alert alert-success"><?= htmlspecialchars($notice) ?></div><?php endif; ?>

    <div class="row g-3 mb-4">
      <div class="col-md-3"><div class="stat-card"><div class="stat-num"><?= count($clients) ?></div><div class="text-secondary small">Registered Members</div></div></div>
      <div class="col-md-3"><div class="stat-card"><div class="stat-num"><?= count($trainers) ?></div><div class="text-secondary small">Active Coaches</div></div></div>
      <div class="col-md-3"><div class="stat-card"><div class="stat-num"><?= count($schedules) ?></div><div class="text-secondary small">Workout Schedules</div></div></div>
      <div class="col-md-3"><div class="stat-card"><div class="stat-num">&#8369;<?= number_format($totalRevenue, 2) ?></div><div class="text-secondary small">Recorded Payments</div></div></div>
    </div>

    <div class="panel">
      <h2 class="h5 fw-bold mb-3">Recent Contact Inquiries ("Connect to Our Team")</h2>
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
</body>
</html>
